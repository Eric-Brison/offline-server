/*!
 * Offline Class
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */

/**
 * @class Fdl.OfflineView
 * @param {Object}
 *            config
 * @cfg {Fdl.context} context the current context connection
 * @cfg {String} name the application name
 * @constructor
 */

/**
 * @param {Object}
 *            config
 *            <ul>
 *            <li><b>context : </b>{Fdl.Context} the current context</li>
 *            <li><b>domain : </b>{Fdl.OfflineDomain} the current domain</li>
 *            </ul>
 * @return {Fdl.Document} reverted document
 */
Fdl.OfflineView = function(config) {
    if (config && config.context) {
        this.context = config.context;
        this.domain = config.domain;

    }
};

Fdl.OfflineView.prototype = {
    /**
     * context
     * 
     * @private
     * @type {Fdl.Context}
     */
    context : null,
    /**
     * context
     * 
     * @private
     * @type {Fdl.OfflineDomain}
     */
    domain : null,
    /**
     * retrieve all family views from server
     * 
     * @param {Object}
     * 
     * 
     * @return {Fdl.Document} reverted document (null if error)
     */
    getFamiliesBindings : function() {
        var config = {};
        config.method = 'getFamiliesBindings';
        var data = this.callViewMethod(config);

        if (data) {
            if (!data.error) {
                return data.bindings;
            }
        }

        return null;
    },

    toString : function() {
        return 'Fdl.OfflineView';
    }
};

/**
 * Call a method for domain api
 * 
 * @param object
 *            config include method attribute and specific parameters
 * @private
 * @return {Object}
 */
Fdl.OfflineView.prototype.callViewMethod = function(config) {
    if (config && config.method) {
        var data = this.context.retrieveData({
            app : 'OFFLINE',
            action : 'OFF_DOMAINAPI',
            method : config.method,
            use : 'view',
            id : this.domain.id
        }, config);

        if (data) {
            if (!data.error) {
                return data;
            } else {
                this.context.setErrorMessage(data.error);
            }
        } else {
            this.context.setErrorMessage(config.method + ' : no data');
        }
    }
    return null;
};