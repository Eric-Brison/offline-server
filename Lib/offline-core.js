/*!
 * Offline Class
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */

/**
 * @class Fdl.OfflineCore
 * @param {Object}
 *            config
 * @cfg {Fdl.context} context the current context connection
 * @cfg {String} name the application name
 * @constructor
 */

Fdl.OfflineCore = function(config) {
	if (config && config.context) {
		this.context = config.context;
		var u = this.context.getUser();
		if (u) {
			this.login = u.login;
		}
		this.context.addFamilyMap({
			familyName : 'OFFLINEDOMAIN',
			className : 'Fdl.OfflineDomain'
		});
	}
};

Fdl.OfflineCore.prototype = {
	/**
	 * context
	 * 
	 * @private
	 * @type {Fdl.Context}
	 */
	context : null,
	/**
	 * user login
	 * 
	 * @type {String}
	 */
	login : null,
	

	/**
	 * @return {Fdl.DocumentList} list of domain documents
	 */
	getOfflineDomains : function() {
	    if (!this.context)
	        return null;

	    try {
	        var data = this.context.retrieveData({
	            app : 'OFFLINE',
	            action : 'OFF_DOMAINAPI',
	            method : 'getDomains'
	        });
	        if (data) {
	            if (!data.error) {
	                data.context = this.context;
	                return new Fdl.DocumentList(data);
	            } else {
	                this.context.setErrorMessage(data.error);
	            }
	        }
	    } catch (ex) {
	        this.context.setErrorMessage('cannot access');
	    }
	    return null;
	},
	/**
	 * version like 1.0.6-3
	 * 
	 * @return {String} the version of offline module
	 */
	getVersion : function() {
		var app = new Fdl.Application({
			context : this.context,
			name : 'OFFLINE'
		});
		if (app) {
			return app.version;
		}

		return 0;
	},
	

	toString : function() {
		return 'Fdl.OfflineCore';
	}
};