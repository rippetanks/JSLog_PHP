/*
  This Source Code Form is subject to the terms of the Mozilla Public
  License, v. 2.0. If a copy of the MPL was not distributed with this
  file, You can obtain one at http://mozilla.org/MPL/2.0/.
*/

/**
*
* @author Simone Martelli
* @version 1.1.0
*/
const JSLog = (function() {

  /**
  * Log levels.
  *
  * @const {Array}
  * @private
  */
  const _LOG_LEVEL = {
    TRACE: ['TRACE', 0],
    DEBUG: ['DEBUG', 1],
    INFO: ['INFO', 2],
    WARN: ['WARN', 3],
    ERROR: ['ERROR', 4],
    ASSERT: ['ASSERT', 4],
    FATAL: ['FATAL', 5]
  };

  /**
  * Standard console object.
  *
  * @private
  */
  const _js_console = console;

  /**
  * Log cache.
  *
  * @private
  * @type {Array}
  */
  let log_cache = [];

  /**
  * Settings for the log library.
  *
  * @prop {string} url_log    URL of the remote log server
  * @prop {string} url_profile  URL of the remote profile server
  * @prop {number} entity     id of the entity to which the log refers
  * @prop {array} levels      log level
  * @prop {string} log_key    api key to access the service
  * @prop {object} ajax
  * @prop {boolean} cache     true if the log cache is enabled, false otherwise
  * @prop {boolean} console_override      true if the library needs to intercept calls to standard console functions
  * @prop {boolean} enable_internal_log   log in the standard console special events
  * @prop {boolean} enable_profile        enable profile feature
  * @prop {function} callback_success
  * @prop {function} callback_error
  * @private
  * @type {Object}
  */
  let settings = {
    url_log: null,
    url_profile: null,
    entity: null,
    level: _LOG_LEVEL.TRACE,
    log_key: null,
    ajax: _ajax,
    cache: null,
    console_override: false,
    enable_internal_log: false,
    enable_profile: false,
    callback_success: null,
    callback_error: null
  };

  /**
  * Standard call to the log server.
  *
  * @param {array} data
  * @private
  */
  function _ajax(data) {
    return $.ajax({
      url: settings.url_log,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: function(data, response, xhr) {
        if(settings.enable_internal_log)
          _js_console.log('JSLog HTTP ' + xhr.status + '!');
        if(settings.callback_success)
          settings.callback_success(data, response, xhr);
      },
      error: function(xhr, response, message) {
        if(settings.enable_internal_log)
          _js_console.error('JSLog HTTP ' + xhr.status + '! ' + message);
        if(settings.callback_error)
          settings.callback_error(xhr, response, message);
      }
    });
  }

  /**
  * Standard call to the profile server.
  *
  * @param {object} data
  * @private
  */
  function _ajax_profile(data) {
    data = jQuery.extend(data, body_cache);
    return $.ajax({
      url: settings.url_profile,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: function(data, response, xhr) {
        if(settings.enable_internal_log)
          _js_console.log('JSLog-Profile HTTP ' + xhr.status + '!');
      },
      error: function(xhr, response, message) {
        if(settings.enable_internal_log)
          _js_console.error('JSLog-Profile HTTP ' + xhr.status + '! ' + message);
      }
    });
  }

  /**
  * Override of the console standard.
  *
  * @private
  */
  function consoleOverride() {
    console = {
      assert: function(assertion, ...msg) {
        if(!settings.enable_internal_log)
          _js_console.assert(assertion, msg);
        if(!assertion)
          JSLog.bulk(_LOG_LEVEL.ASSERT, msg);
      },
      debug: function(...msg) {
        if(!settings.enable_internal_log)
          _js_console.debug(msg);
        return JSLog.bulk(_LOG_LEVEL.DEBUG, msg);
      },
      error: function(...msg) {
        if(!settings.enable_internal_log)
          _js_console.error(msg);
        return JSLog.bulk(_LOG_LEVEL.ERROR, msg);
      },
      info: function(...msg) {
        if(!settings.enable_internal_log)
          _js_console.info(msg);
        return JSLog.bulk(_LOG_LEVEL.INFO, msg);
      },
      log: function(...msg) {
        if(!settings.enable_internal_log)
          _js_console.info(msg);
        return JSLog.bulk(_LOG_LEVEL.INFO, msg);
      },
      trace: function(...msg) {
        if(!settings.enable_internal_log)
          _js_console.trace(msg);
        return JSLog.bulk(_LOG_LEVEL.TRACE, msg);
      },
      warn: function(...msg) {
        if(!settings.enable_internal_log)
          _js_console.warn(msg);
        return JSLog.bulk(_LOG_LEVEL.WARN, msg);
      }
    };
  }

  /**
  * Inserts the message in the queue or sends the message to the server
  * if the cache is disabled or full.
  *
  * @param {array} array
  * @private
  */
  function _log(array) {
    // check on the log level
    let filtered = array.filter(function(v) {
      return v.level[1] >= settings.level[1];
    });
    // the init function must be called before any use
    if(!body_cache) {
      _js_console.warn('Call the init function first!');
      return;
    }

    filtered.forEach(function(e) {
      e.level = e.level[0];
      e = jQuery.extend(e, body_cache);
      if(!e.httpCode)
        delete e.httpCode;
    });

    return _ajax(filtered);
  }

  /**
  * Get the standard body.
  *
  * @private
  */
  function getBody() {
    let obj = {};
    if(settings.entity)
      obj.entity = settings.entity;
    if(settings.log_key)
      obj.log_key = settings.log_key;
    return obj;
  }
  let body_cache = undefined;

  // public
  return {

    /**
    * Initialization. Call this function first of all.
    *
    * @param {object} options configuration options identical to settings, only URL is required
    * @public
    */
    init: function(options) {
      settings = jQuery.extend(settings, options);
      if(!settings.url_log) {
        console.warn("Please, set a url_log!");
      }
      if(settings.console_override)
        this.override();
      body_cache = getBody();
    },

    /**
    * Inserts a line in the log.
    *
    * @param {LOG_LEVEL} level  level of log
    * @param {string} message   message to log
    * @param {number} httpCode  http code (optional)
    * @public
    */
    log: function(level, message, httpCode) {
      // control on the completeness of the data
      if(!level || !message) {
        _js_console.warn('Incomplete data!');
        return false;
      }
      // cache or send directly
      if(settings.cache >= 1) {
        // put into cache
        log_cache.push({
          level: level,
          message: message,
          httpCode: httpCode,
          datetime: new Date().toISOString()
        });
        // full cache
        if(log_cache.length === settings.cache) {
          let cache = Array.from(log_cache);
          log_cache = [];
          return _log(cache);
        }
        else
          return false;
      }
      // send directly
      else {
        return _log([{
          level: level,
          message: message,
          httpCode: httpCode,
          datetime: new Date().toISOString()
        }]);
      }
    },

    /**
    * Inserts a series of messages into the log.
    *
    * @param {LOG_LEVEL} level  level of log
    * @param {msg} msg          messages to add to the log
    * @public
    */
    bulk: function(level, ...msg) {
      let d = [];
      for(let i = 0; i < msg.length; i++)
        d[i] = this.log(level, msg[i]);
      return d;
    },

    /**
    * Alias per log (LOG_LEVEL.TRACE, message, httpCode).
    * @public
    */
    trace: function(message, httpCode) {
      return this.log(_LOG_LEVEL.TRACE, message, httpCode);
    },

    /**
    * Alias per log (LOG_LEVEL.DEBUG, message, httpCode).
    * @public
    */
    debug: function(message, httpCode) {
      return this.log(_LOG_LEVEL.DEBUG, message, httpCode);
    },

    /**
    * Alias per log (LOG_LEVEL.INFO, message, httpCode).
    * @public
    */
    info: function(message, httpCode) {
      return this.log(_LOG_LEVEL.INFO, message, httpCode);
    },

    /**
    * Alias per log (LOG_LEVEL.TRACE, message, httpCode).
    * @public
    */
    warn: function(message, httpCode) {
      return this.log(_LOG_LEVEL.WARN, message, httpCode);
    },

    /**
    * Alias per log (LOG_LEVEL.ERROR, message, httpCode).
    * @public
    */
    error: function(message, httpCode) {
      return this.log(_LOG_LEVEL.ERROR, message, httpCode);
    },

    /**
    * Alias per log (LOG_LEVEL.FATAL, message, httpCode).
    * @public
    */
    fatal: function(message, httpCode) {
      return this.log(_LOG_LEVEL.FATAL, message, httpCode);
    },

    /**
    * Returns the current log level.
    * @return {LOG_LEVEL} current log level
    * @public
    */
    getLevel: function() {
      return settings.level;
    },
    /**
    * Returns the current log level.
    * @return {string} current log level
    * @public
    */
    getLevelString: function() {
      return settings.level[0];
    },
    /**
    * Set the log level.
    * @param {LOG_LEVEL} new log level
    * @public
    */
    setLevel: function(level) {
      settings.level = level;
    },

    /**
    * Override the standard console.
    * @public
    */
    override: function() {
      consoleOverride();
    },
    /**
    * Returns the native console.
    * @return {object} native console
    * @public
    */
    nativeConsole: function() {
      return _js_console;
    },

    /**
    * Enable the logger cache.
    * @param {number} n   cache size
    * @public
    */
    enableCache: function(n) {
      settings.cache = n;
    },
    /**
    * Disable the logger cache.
    * @public
    */
    disableCache: function() {
      settings.cache = null;
    },
    /**
    * Return the cache size.
    * @return {number} cache size
    * @public
    */
    cacheSize: function() {
      return log_cache.length;
    },

    /**
    * Clears the cache by sending the remaining messages to the server.
    * @return {boolean} false if cache is empty
    * @public
    */
    flush: function() {
      if(log_cache.length > 0)
        return _log(log_cache);
      return false;
    },

    /**
    * Log level.
    * @public
    */
    LOG_LEVEL: _LOG_LEVEL,

    /**
    *
    * @param {number}
    * @public
    */
    profile: function(startTime, desc) {
      if(!settings.enable_profile)
        return -1;
      // start profile
      if(startTime == undefined) {
        return new Date().getTime();
      }
      // end profile
      else {
        let now = new Date().getTime();
        let time = now - startTime;
        return _ajax_profile({'time': time, 'desc': desc});
      }
    },

    /**
    *
    * @public
    */
    disableProfile: function() {
      settings.enable_profile = false;
    },
    /**
    *
    * @public
    */
    enableProfile: function() {
      settings.enable_profile = true;
    }

  };
})();

// freeze objects
Object.freeze(JSLog.LOG_LEVEL);
Object.freeze(JSLog);
