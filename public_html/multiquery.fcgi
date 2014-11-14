#!/usr/bin/python
# -*- coding: utf-8 -*-

from flup.server.fcgi import WSGIServer
import threading
import time
import redis
import oursql
import requests
import hashlib
import json
import sys
import re
from cgi import parse_qs
from ConfigParser import SafeConfigParser

f = open('/data/project/xtools/mq.log', 'a')
sys.stderr = f 


class ThreadQuery(threading.Thread):

    def __init__ (self, qtype, src, timeout, query, mredis, ttl, dbUser, dbPwd, defaultServer, defaultDatabase):

        threading.Thread.__init__(self)
        self.qtype = qtype
        self.src = src
        self.timeout = None if ( int(timeout) == 0 ) else timeout
        self.query = query
        self.qlen = 0
        self.token = None
        self.starttime = time.time()
        self.duration = 'error'
        self.redis = mredis
        self.ttl = ttl
        self.dbUser = dbUser
        self.dbPwd = dbPwd
        self.server = defaultServer
        self.database = defaultDatabase
        self.error = None

    def run(self):
        
        if self.qtype == 'db': 

            if self.src[:5] == 'slice':
                self.server = self.src[6:]
                self.database = '';
            elif self.src != 'this':
                self.server = self.src + '.labsdb'
                self.database = self.src + '_p';
            
            qdata = self.db_query()
        else:
            qdata = self.api_query()
        
        str = self.qtype + self.src + self.query
        self.token = hashlib.md5( str.encode('utf-8', 'ignore') ).hexdigest()
        
        try:
            self.redis.setex( self.token, json.dumps( qdata ), self.ttl )
            self.qlen = self.redis.strlen( self.token )
        except:
            sys.stderr.write( 'json.dumps' )
            sys.stderr.write( self.query + ' : ' + self.database )
            sys.stderr.flush()
        
        del qdata
        return
        
    def db_query(self):
        
        try:
            db = oursql.connect(host=self.server, user=self.dbUser, passwd=self.dbPwd, db=self.database, charset='utf8', raise_on_warnings=False ) 
            cursor = db.cursor( oursql.DictCursor )
        except Exception as e:
            sys.stderr.write( self.src + "\n" + self.database + "\n" + str(e) + "\n")
            self.error = str(e)
            return []
        
           
        if ( re.search('Insert', self.query, flags=re.IGNORECASE) ):
            isSelect = False
            cursor.execute( 'SET autocommit=0', params=False, plain_query=True )
            cursor.execute( self.query, params=False, plain_query=True )
            cursor.execute( 'SET autocommit=1', params=False, plain_query=True )
            qdata = ['INSERT']
        else:
            isSelect = True 
            cursor.execute( self.query, params=False, plain_query=True )    
            qdata = []
        
        
        if ( isSelect and cursor.rowcount ) > 0:
            try:
                qdata = cursor.fetchall()
            except Exception as e:
                sys.stderr.write( self.src + "\n" + self.query + "\n" + str(e) + "\n")
                self.error = str(e)
                qdata = []
            finally:
                cursor.close()
                db.close()

        del cursor, db
        self.duration = (time.time() - self.starttime)
        return qdata
    
    def api_query(self):
        headers = {'User-Agent': 'Xtools/2.0 (https://tools.wmflabs.org/xtools/)'}

        try:
            if (self.src == "head"):
                req = requests.head(self.query, timeout = self.timeout)
                qdata = [req.status_code]            
            elif (self.src == "plainhtml"):
                req = requests.get(self.query, timeout = self.timeout, headers=headers)
                qdata = req.text
            else:
                req = requests.get(self.query, timeout = self.timeout)
                qdata = json.loads(req.text)
            
            del req            
        except Exception as e:
            sys.stderr.write( self.src + "\n" + self.query + "\n" + str(e) + "\n")
            self.error = str(e)
            qdata = []
            
        self.duration = (time.time() - self.starttime)
        return qdata
        

def app(environ, start_response):
    
    ttl = 100

    d = parse_qs(environ['QUERY_STRING'])
    reqToken = d.get("reqToken",[''])[0]
    
    if (not reqToken):
        start_response('200 OK', [('Content-Type', 'application/json')])
        return ['nothing to process']

    r = redis.Redis(host='tools-redis')

    queriesInput = json.loads( r.get(reqToken) )
    
    parser = SafeConfigParser()
    parser.read('/data/project/xtools/replica.my.cnf')
    dbUser = parser.get('client', 'user').strip("'")
    dbPwd = parser.get('client', 'password').strip("'")

    results = []
    for part in queriesInput['queries']:
        current = ThreadQuery( qtype=part["type"], src=part["src"], timeout=part["timeout"], query=part["query"], mredis=r, ttl=ttl, dbUser=dbUser, dbPwd=dbPwd, defaultServer=queriesInput['defaultServer'], defaultDatabase=queriesInput['defaultDatabase'])
        results.append(current)
        current.start()

    output = []
    for el in results:
        el.join()
        output.append({"token": el.token, "len": el.qlen, "duration": el.duration, "error": el.error })
        del el

    r.setex( reqToken + "_response", json.dumps(output), ttl )
    
    start_response('200 OK', [('Content-Type', 'application/json')])
    return [reqToken]


if __name__ == '__main__':
    WSGIServer(app).run()

    
