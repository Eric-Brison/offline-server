/*!
 * Offline Class
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */

/**
 * @class Fdl.OfflineDomain
 * @param {Object}
 *            config
 * @cfg {Fdl.context} context the current context connection
 * @cfg {String} name the application name
 * @constructor
 */

Fdl.OfflineDomain = function(config) {
	Fdl.Document.call(this, config);
};
Fdl.OfflineDomain.prototype = new Fdl.Document();
Fdl.OfflineDomain.prototype.toString = function() {
	return 'Fdl.OfflineDomain';
};

/**
 * Call a method for domain api
 * 
 * @param object
 *            config include method attribute and specific parameters
 * @private
 * @return {Object}
 */
Fdl.OfflineDomain.prototype.callDomainMethod = function(config) {
	if (config && config.method) {
		var data = this.context.retrieveData({
			app : 'OFFLINE',
			action : 'OFF_DOMAINAPI',
			method : config.method,
			id : this.id
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

/**
 * retrieve content (recursive) of shared folder
 * 
 * @param object config
 *            <ul>
 *            <li><b>until : </b>{String} date since documents has been
 *            modified</li>
 *            </ul>
 * @return {Fdl.DocumentList} list of document of user's domain
 */
Fdl.OfflineDomain.prototype.getSharedDocuments = function(config) {
	var until = null;
	if (config && config.until) {
		until = config.until;
	}
	var data = this.callDomainMethod({
		method : 'getSharedDocuments',
		until : until
	});
	if (data) {
		data.context = this.context;
		return new Fdl.DocumentList(data);

	} else {
		return null;
	}
};

/**
 * get user mode : standard or advanced
 * 
 * @return {String} "standard" or "advanced"
 */
Fdl.OfflineDomain.prototype.getUserMode = function() {
	if (!this.userMode) {
		var data = this.callDomainMethod({
			method : 'getUserMode'
		});
		if (data) {
			this.userMode = data.userMode;
		} else {
			return null;
		}
	}
	return this.userMode;
};
/**
 * retrieve content (recursive) of user folder
 * @param object config
 *            <ul>
 *            <li><b>until : </b>{String} date since documents has been
 *            modified</li>
 *            </ul>
 * @return {Fdl.DocumentList} list of document of user's domain
 */
Fdl.OfflineDomain.prototype.getUserDocuments = function(config) {
	var until = null;
	if (config && config.until) {
		until = config.until;
	}
	var data = this.callDomainMethod({
		method : 'getUserDocuments',until:until
	});
	if (data) {
		data.context = this.context;
		return new Fdl.DocumentList(data);

	} else {
		return null;
	}

};

/**
 * retrieve all document id of reserved document for current user ids are
 * initial id (initid)
 * 
 * @return {Array} of integer
 */
Fdl.OfflineDomain.prototype.getReservedDocumentIds = function() {
	var data = this.callDomainMethod({
		method : 'getReservedDocumentIds'
	});
	if (data) {
		return data.reservedDocumentIds;
	}
	return null;
};

/**
 * retrieve family document set in offline domain
 * 
 * @return {Fdl.DocumentList} list of document family
 */
Fdl.OfflineDomain.prototype.getAvailableFamilies = function() {
	if (!this.availableFamilies) {
		var data = this.callDomainMethod({
			method : 'getAvailableFamilies'
		});
		if (data) {
			data.context = this.context;
			return new Fdl.DocumentList(data);
		} else {
			return null;
		}
	}
	return this.availableFamilies;
};
/**
 * put a lock to document
 * 
 * @param {Object}
 *            config
 *            <ul>
 *            <li><b>document : </b>{Fdl.Document} the document to reserve</li>
 *            </ul>
 * @return {Fdl.Document} reverted document (null if error)
 */
Fdl.OfflineDomain.prototype.bookDocument = function(config) {
	if (config && config.document) {
		config.method = 'bookDocument';
		config.docid = config.document.id;
		var data = this.callDomainMethod(config);

		if (data) {
			if (!data.error) {
				return this.context.getDocument({
					data : data
				});
			}
		}
	}

	return null;
};

/**
 * cancel the reservation if local document is changed a revert is also
 * completed
 * 
 * @param {Object}
 *            config
 *            <ul>
 *            <li><b>document : </b>{Fdl.Document} the document where cancel
 *            the reservation</li>
 *            </ul>
 * @return {Fdl.Document} cancelled document (null if error)
 */
Fdl.OfflineDomain.prototype.unbookDocument = function(config) {
	if (config && config.document) {
		config.method = 'unbookDocument';
		config.docid = config.document.id;
		var data = this.callDomainMethod(config);

		if (data) {
			if (!data.error) {
				return this.context.getDocument({
					data : data
				});
			}
		}
	}

	return null;
};

/**
 * delete waiting changes and return update document 
 * 
 * @param {Object}
 *            config
 *            <ul>
 *            <li><b>document : </b>{Fdl.Document} the document where cancel
 *            the reservation</li>
 *            </ul>
 * @return {Fdl.Document} reverted document (null if error)
 */
Fdl.OfflineDomain.prototype.revertDocument = function(config) {
	if (config && config.document) {
		config.method = 'revertDocument';
		config.docid = config.document.id;
		var data = this.callDomainMethod(config);

		if (data) {
			if (!data.error) {
				return this.context.getDocument({
					data : data
				});
			}
		}
	}

	return null;
};

/**
 * remove document from user space the document is not deleted. It is just
 * remove from collection set to synchronize
 * 
 * @param {Object}
 *            config
 *            <ul>
 *            <li><b>document : </b>{Fdl.Document} the document to remove</li>
 *            </ul>
 * @return {Fdl.Document} removed document (null if error)
 */
Fdl.OfflineDomain.prototype.removeDocument = function(config) {
	if (config && config.document) {
		config.method = 'removeDocument';
		config.docid = config.document.id;
		var data = this.callDomainMethod(config);

		if (data) {
			if (!data.error) {
				return this.context.getDocument({
					data : data
				});
			}
		}
	}
	return null;
};

/**
 * @return {Array} list of xml form template for each available families
 */
Fdl.OfflineDomain.prototype.getFamilyForms = function() {
};
/**
 * @return {Array} list of xml template for each available families
 */
Fdl.OfflineDomain.prototype.getFamilyViews = function() {
};

/**
 * list of file content needed to change skin (overlay.css or others)
 * 
 * @return {Object}
 */
Fdl.OfflineDomain.prototype.getSkin = function() {
};

Fdl.OfflineDomain.prototype.syncObject = null;
/**
 * return sync object
 * 
 * @returns {Fdl.OfflineSync}
 */
Fdl.OfflineDomain.prototype.sync = function() {
	if (!this.syncObject) {
		this.syncObject = new Fdl.OfflineSync({
			context : this.context,
			domain : this
		});
	}
	return this.syncObject;
};


Fdl.OfflineDomain.prototype.viewObject = null;
/**
 * return sync object
 * 
 * @returns {Fdl.OfflineSync}
 */
Fdl.OfflineDomain.prototype.view = function() {
    if (!this.viewObject) {
        this.viewObject = new Fdl.OfflineView({
            context : this.context,
            domain : this
        });
    }
    return this.viewObject;
};