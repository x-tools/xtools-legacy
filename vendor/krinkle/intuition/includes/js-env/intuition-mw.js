/*global mw */
(function ($) {
	var intuition, userlang, queueTimeout, queueDeferred,
		apiPath = 'api.php',
		queueList = [],
		requested = {},
		hasOwn = requested.hasOwnProperty,
		push = queueList.push,
		slice = queueList.slice,
		mwMsgPrefix = 'intuition-';

	function handleQueue() {
		var list = queueList.splice(0, queueList.length),
			deferred = queueDeferred;

		queueDeferred = queueTimeout = undefined;

		$.ajax({
			url: apiPath,
			data: {
				domains: list.join('|'),
				userlang: userlang
			},
			dataType: $.support.cors ? 'json' : 'jsonp'
		}).fail(deferred.reject).done(function (data) {
			if (!data || !data.messages) {
				return deferred.reject();
			}
			$.each(data.messages, intuition.put);
			deferred.resolve();
		});
	}

	intuition = {
		/**
		 * @param {string|Array} domains
		 * @param {string} [lang=wgUserLanguage] Only one language is supported. Last one wins.
		 * @return {jQuery.Promise}
		 */
		load: function (domains, lang) {
			var i,
				list = [];

			domains = typeof domains === 'string' ? [domains] : domains;

			for (i = 0; i < domains.length; i++) {
				if (!hasOwn.call(requested, domains[i])) {
					requested[domains[i]] = true;
					list.push(domains[i]);
				}
			}

			if (!list.length) {
				return $.Deferred().resolve();
			}

			// Defer request so we can perform them in batches
			userlang = lang || mw.config.get('wgUserLanguage', 'en');
			push.apply(queueList, list);

			if (!queueDeferred) {
				queueDeferred = $.Deferred();
			}

			if (queueTimeout) {
				clearTimeout(queueTimeout);
			}
			queueTimeout = setTimeout(handleQueue, 100);

			return queueDeferred.promise();
		},

		put: function (domain, msgs) {
			requested[domain] = true;
			if (msgs) {
				$.each(msgs, function (key, val) {
					mw.messages.set(mwMsgPrefix + domain + '-' + key, val);
				});
			}
		},

		/**
		 * @param {string} domain
		 * @param {string} key
		 * @param {Mixed...} [parameters]
		 * @return {string}
		 */
		msg: function (domain, key) {
			var args = slice.call(arguments, 2);
			args.unshift(mwMsgPrefix + domain + '-' + key);
			return mw.message.apply(mw.message, args).toString();
		},

		/**
		 * @param {string} domain
		 * @param {string} key
		 * @param {Mixed...} [parameters]
		 * @return {mw.Message}
		 */
		message: function (domain, key) {
			var args = slice.call(arguments, 2);
			args.unshift(mwMsgPrefix + domain + '-' + key);
			return mw.message.apply(mw.message, args);
		}
	};

	// Expose
	mw.libs.intuition = intuition;

}(jQuery));
