/**
 * Created by vetermanve on 22/12/2017.
 */

window.events = new window.EventEmitter3();

var transportProto = {
    REST         : 'rest',
    PAGE         : 'page',
    connections  : {},
    meta         : {},
    setConnection: function (connection, type) {
        type = type || this.REST;
        this.connections[type] = connection
    },
    call         : function (method, resource, data, success, error) {
        this.connections[this.REST].call(method, resource, data, success, error);
    },
    loadPage     : function (method, resource, data, success, error) {
        this.connections[this.PAGE].call(method, resource, data, success, error);
    }
};

var clientProto = {
    serverAddress : '', 
    deviceId : '',
    salt : '',
    init : function ()
    {
        window.events.on('socketConnect', this.setUp, this);
    },
    setUp : function ()
    {
        var self = this;
        var address = transport.call('get', '/socket/connection/address', {}, function (data)
        {
            if (data && typeof data.address !== 'undefined') {
                self.setAddress(data.address);
            }
        });
    },
    setAddress : function (address)
    {
        this.serverAddress = address;
        this.loadDevice();
        this.updateDevice();
    },
    loadDevice : function ()
    {
        this.deviceId = localStorage.getItem('deviceId') || '';
        this.salt = localStorage.getItem('salt') || '';
    },
    updateDevice : function ()
    {
        var self = this;
        
        var deviceId = this.deviceId;
        var salt = this.salt;
        
        if (deviceId.length > 0 && salt.length > 0) {
            transport.call('put', 'rest/platform-clients', {
                "id": deviceId,
                "salt": salt,
                "address": this.serverAddress
            },  function (data)
            {
                //console.log('device address updated', data);
            });
        } else {
            var bind = {
                "type"  : "web",
                "ownerId" : '',
                "ownerType" : 'user',
                "address" : this.serverAddress,
                "key" : uuidV4(),
                "salt" : uuidV4() + uuidV4() + uuidV4(),
                "version" : window.mutantClientVersion || 0
            };
            transport.call('post', 'rest/platform-clients', bind, this.saveDevice.bind(self), function (err)
            {
                console.error(err);
            });
        }
    },
    saveDevice : function (device) {
        this.deviceId = device.id;
        this.salt = device.salt;
        
        localStorage.setItem('deviceId', this.deviceId);
        localStorage.setItem('salt', this.salt);   
    }
};

var ajaxConnection = {
    host : 'http://localhost/rest/',
    type : 'json',
    init : function (meta) {
        this.host = meta['host'] || this.host;
    },
    call : function (method, resource, data, success, error) {
        var self = this;
        $.ajax({
            url : self.host + '/' + resource,
            cache: false,
            type: method,
            dataType: self.type,
            data: data,
            success: function (data) {
                self.log('successful ' + method +  ':' + resource, data);
                success && success(data);
            },
            error: function (data) {
                self.log('error ' + method +  ':' + resource, data);
                error && error(data);
            }
        });
    },
    log : function (text, data) {
        if(data) {
            console.log('ajax: ' + text , data);
        } else {
            console.log('ajax: ' + text);
        }
    }
};

var uuidV4 = function b(a){return a?(a^Math.random()*16>>a/4).toString(16):([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g,b)};

var ClientRequest =  {
    init : function (uuid, method, path, query, data, headers, state) {
        this.uuid = uuid || uuidv4();
        this.method = method;
        this.path = path || '';
        this.query = query || '';
        this.data = data || {};
        this.headers = headers || {};
        this.state = state || {};
        this.born = Date.now()/1000;
    }
};

var socketConnection = {
    host : 'http://localhost',
    prefix : '',
    type : 'json',
    response : {},
    init : function (meta) {
        meta = meta || {};
        this.host = meta['host'] || this.host;
        this.socket = window.io(this.host);
        this.response = {};
        var self = this;

        this.socket.on('response', function (msg) {
            self._response(msg);
        });

        this.socket.on('connect', function(){
            window.events.emit('socketConnect');
        });
        
        this.socket.on('reconnect', function (){
            window.events.emit('socketConnect');
        });

        this.socket.on('event', function (msg) {
            if (msg && msg.type && msg.data) {
                window.events.emit(msg.type, msg.data);    
                console.log("Event emitted", msg.type, msg.data);
            }
        });
    },
    _response : function (msg)
    {
        if (typeof this.response[msg.reply_uuid] === 'undefined') {
            console.warn(msg);
            console.error("No reply found", msg);
            return;
        }
        
        if (typeof msg.state === 'object' && Object.keys(msg.state).length) {
            for (var stateKey in msg.state) {
                Cookies.set(stateKey, msg.state[stateKey][0], {expires : msg.state[stateKey][1]});
            }
        }
        
        var callbacks = this.response[msg.reply_uuid];
        clearTimeout(callbacks.t);
        
        delete this.response[msg.reply_uuid];

        console.log('socket response on: ' + callbacks.p, msg);
            
        if (msg.code === 200 || msg.code === 201) {
            callbacks.s && callbacks.s(msg.data);
        } else {
            callbacks.e && callbacks.e(msg.data);
        }
    },
    call : function (method, resource, data, success, error) {
        var self = this;
        
        if (Array.isArray(data)) {
           var newData = {};
           for (var key in data) {
               if (data[key]['name'] !== 'undefined' && data[key]['value'] !== undefined) {
                   newData[data[key]['name']] =  data[key]['value'];
               }
           }
           
           data = newData;
        }

        var request = Object.create(ClientRequest);
        var requestId = uuidV4();
        
        if (success || error)
        {
            this.response[requestId] = {
                p : method + " " + resource,
                s : success,
                e : error,
                t : setTimeout(this._response.bind(this), 3000, {
                    code : 502,
                    reply_uuid : requestId,
                    data : {
                        msg : "clientTimeout"
                    }
                })
            };
        }
                
        request.init(
            requestId,
            method,
            resource,
            {},
            data,
            {
                "Origin" :  window.location.host
            },
            {}
        );

        this.socket.emit('request', request);
        
        console.log('socket request: ' + method + " " + resource , data);
    } 
};

var setupForm = function(obj, resource, success, error, method, beforeSend) {
    method = method || 'post';
    var frm = $(obj);
    frm.submit(function(e) {
        var data = frm.serializeArray();
        e.preventDefault();
        if (beforeSend) {
            try {
                data = beforeSend(data) || data;
            } catch (e) {
                console.error(e);
            }
        }
        
        transport.call(method, 'rest/' + resource, data, success, error);
    });
};

var nav = {
    go : function (page, data, preventHistory) {
        data = data || {};
        data['_layout'] = 'noheader';
        transport.loadPage('get', '/web' + page, data, function (html)
            {
                $('#page-content').html(html);
                $('body').scrollTop(0);
                preventHistory || window.history.pushState({}, page, page)
            }
        );
    },
    goState : function (state) {
        this.go(state.target.location.pathname, {}, true);
    },
    init : function ()
    {
        var self = this;
        $('body').on('click', 'a', function(event) {
                event.preventDefault();
                self.go($(this).attr("href"));
            }
        );

        window.onpopstate = function (event)
        {
          self.goState(event);  
        };
    }
};