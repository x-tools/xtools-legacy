// <nowiki>
(function() {
  if (!/Wikipedia:Requests for permissions\//.test(document.title)) {
    return;
  }

  var permissionNames = {
    'Account creator': 'accountcreator',
    'Autopatrolled': 'autoreviewer',
    'Confirmed': 'confirmed',
    'File mover': 'filemover',
    'Mass message sender': 'massmessage-sender',
    'Pending changes reviewer': 'reviewer',
    'Rollback': 'rollbacker',
    'Template editor': 'templateeditor'
  };

  var templates = {
    'Account creator': 'Account creator granted',
    'Autopatrolled': 'Autopatrolledgiven',
    'AutoWikiBrowser': '',
    'Confirmed': '',
    'File mover': 'Filemovergiven',
    'Mass message sender': 'Mass message sender granted',
    'Pending changes reviewer': 'Pending changes reviewer granted',
    'Rollback': 'Rollbackgiven3',
    'Template editor': 'Template editor granted'
  };

  var api = new mw.Api(),
    permission = mw.config.get('wgTitle').split('/').slice(-1)[0],
    revisionId = mw.config.get('wgRevisionId'),
    tagLine = ' (using [[User:MusikAnimal/userRightsManager.js|userRightsManager]])',
    permaLink, userName, dialog;

  mw.loader.using(['oojs-ui'], function() {
    $('.sysop-show a').on('click', function(e) {
      if (permission === 'AutoWikiBrowser') return true;
      e.preventDefault();
      userName = $(this).parents('.plainlinks').find('a').eq(0).text();
      showDialog();
    });
  });

  function showDialog() {
    Dialog = function(config) {
      Dialog.super.call(this, config);
    };
    OO.inheritClass(Dialog, OO.ui.ProcessDialog);
    Dialog.static.title = 'Grant ' + permission + ' to ' + userName;
    Dialog.static.actions = [
      { action: 'submit', label: 'Submit', flags: ['primary', 'constructive'] },
      { label: 'Cancel', flags: 'safe' }
    ];
    Dialog.prototype.getApiManager = function() {
      return this.apiManager;
    };
    Dialog.prototype.getBodyHeight = function() {
      return 140;
    };
    Dialog.prototype.initialize = function() {
      Dialog.super.prototype.initialize.call( this );
      this.editFieldset = new OO.ui.FieldsetLayout( {
        classes: ['container']
      });
      this.editPanel = new OO.ui.PanelLayout({
        expanded: false
      });
      this.editPanel.$element.append( this.editFieldset.$element );
      this.rightsChangeSummaryInput = new OO.ui.TextInputWidget({
        value: 'Requested at [[WP:PERM]]'
      });
      this.closingRemarksInput = new OO.ui.TextInputWidget({
        value: '{{done}} ~~~~'
      });
      this.watchTalkPageCheckbox = new OO.ui.CheckboxInputWidget({
        selected: false
      });
      this.editFieldset.addItems( [
        new OO.ui.FieldLayout(this.rightsChangeSummaryInput, {
          label: 'Summary'
        }),
        new OO.ui.FieldLayout(this.closingRemarksInput, {
          label: 'Closing remarks'
        }),
        new OO.ui.FieldLayout(this.watchTalkPageCheckbox, {
          label: 'Watch user talk page'
        })
      ] );
      this.submitPanel = new OO.ui.PanelLayout( {
        $: this.$,
        expanded: false
      } );
      this.submitFieldset = new OO.ui.FieldsetLayout( {
        classes: ['container']
      } );
      this.submitPanel.$element.append( this.submitFieldset.$element );
      this.changeRightsProgressLabel = new OO.ui.LabelWidget();
      this.changeRightsProgressField = new OO.ui.FieldLayout( this.changeRightsProgressLabel );
      this.markAsDoneProgressLabel = new OO.ui.LabelWidget();
      this.markAsDoneProgressField = new OO.ui.FieldLayout( this.markAsDoneProgressLabel );
      this.issueTemplateProgressLabel = new OO.ui.LabelWidget();
      this.issueTemplateProgressField = new OO.ui.FieldLayout( this.issueTemplateProgressLabel );
      this.stackLayout = new OO.ui.StackLayout( {
        items: [this.editPanel, this.submitPanel],
        padded: true
      } );
      this.$body.append( this.stackLayout.$element );
    };

    Dialog.prototype.onSubmit = function() {
      var self = this, promiseCount = 3;

      self.actions.setAbilities( { submit: false } );

      addPromise = function( field, promise ) {
        self.pushPending();
        promise.done(function() {
          field.$field.append( $( '<span>' )
            .text( 'Complete!' )
            .prop('style', 'position:relative; top:0.5em; color: #009000; font-weight: bold')
          );
        }).fail(function(obj) {
          if ( obj && obj.error && obj.error.info ) {
            field.$field.append( $( '<span>' )
              .text('Error: ' + obj.error.info)
              .prop('style', 'position:relative; top:0.5em; color: #cc0000; font-weight: bold')
            );
          } else {
            field.$field.append( $( '<span>' )
              .text('An unknown error occurred.')
              .prop('style', 'position:relative; top:0.5em; color: #cc0000; font-weight: bold')
            );
          }
        }).always( function() {
          promiseCount--; // FIXME: maybe we could use a self.isPending() or something
          self.popPending();

          if (promiseCount === 0) {
            setTimeout(function() {
              location.reload(true);
            }, 1000);
          }
        });

        return promise;
      };

      self.markAsDoneProgressField.setLabel( 'Marking request as done...' );
      self.submitFieldset.addItems( [self.markAsDoneProgressField] );
      self.changeRightsProgressField.setLabel( 'Assigning rights...' );
      self.submitFieldset.addItems( [self.changeRightsProgressField] );
      self.issueTemplateProgressField.setLabel( 'Issuing template...' );
      self.submitFieldset.addItems( [self.issueTemplateProgressField] );

      addPromise(
        self.markAsDoneProgressField,
        markAsDone('\n::' + this.closingRemarksInput.getValue())
      ).then(function(data) {
        addPromise(
          self.changeRightsProgressField,
          assignPermission(
            this.rightsChangeSummaryInput.getValue() + tagLine,
            data.edit.newrevid
          )
        );
      }.bind(this));
      addPromise(
        self.issueTemplateProgressField,
        issueTemplate(this.watchTalkPageCheckbox.isSelected())
      );

      self.stackLayout.setItem( self.submitPanel );
    };

    Dialog.prototype.getActionProcess = function( action ) {
      return Dialog.super.prototype.getActionProcess.call( this, action ).next( function() {
        if ( action === 'submit' ) {
          return this.onSubmit();
        } else {
          return Dialog.super.prototype.getActionProcess.call( this, action );
        }
      }, this );
    };

    dialog = new Dialog({
      size: 'medium'
    });

    var windowManager = new OO.ui.WindowManager();
    $('body').append(windowManager.$element);
    windowManager.addWindows([dialog]);
    windowManager.openWindow(dialog);
  }

  function assignPermission(summary, revId) {
    console.log('Assigning permission');
    permaLink = '[[Special:PermaLink/' + revId + '#User:' + userName + ']]';
    return api.postWithToken( 'userrights', {
      action: 'userrights',
      format: 'json',
      user: userName,
      add: permissionNames[permission],
      reason: summary + '; ' + permaLink
    });
  }

  function markAsDone(closingRemarks) {
    console.log('Marking as done');
    var sectionNode = document.getElementById('User:' + userName),
      sectionNumber = $(sectionNode).siblings('.mw-editsection').find('a').prop('href').match(/section=(\d)/)[1];
    return api.postWithToken( 'edit', {
      format: 'json',
      action: 'edit',
      title: mw.config.get('wgPageName'),
      section: sectionNumber,
      summary: 'done' + tagLine,
      appendtext: closingRemarks
    });
  }

  function issueTemplate(watch) {
    console.log('Issuing template');
    var talkPage = 'User talk:' + userName;
    return api.postWithToken( 'edit', {
      format: 'json',
      action: 'edit',
      title: talkPage,
      section: 'new',
      summary: permission + ' granted per ' + permaLink + tagLine,
      text: '{{subst:' + templates[permission] + '}}',
      sectiontitle: permission + ' granted',
      watchlist: watch ? 'watch' : 'unwatch'
    });
  }
})();
// </nowiki>
