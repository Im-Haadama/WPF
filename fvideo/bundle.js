!function () {
    for (var e = function (e) {
        var t;
        return function (r) {
            return t || e(t = {exports: {}, parent: r}, t.exports), t.exports
        }
    }, t = e((function (e, t) {
        "use strict";
        var r = so.Readable;

        function i(e, t, n) {
            r.call(this, t), this._helper = e;
            var i = this;
            e.on("data", (function (e, t) {
                i.push(e) || i._helper.pause(), n && n(t)
            })).on("error", (function (e) {
                i.emit("error", e)
            })).on("end", (function () {
                i.push(null)
            }))
        }

        n({}).inherits(i, r), i.prototype._read = function () {
            this._helper.resume()
        }, e.exports = i
    })), r = e((function (e, t) {
        "use strict";

        function r(e) {
            this.name = e || "default", this.streamInfo = {}, this.generatedError = null, this.extraStreamInfo = {}, this.isPaused = !0, this.isFinished = !1, this.isLocked = !1, this._listeners = {
                data: [],
                end: [],
                error: []
            }, this.previous = null
        }

        r.prototype = {
            push: function (e) {
                this.emit("data", e)
            }, end: function () {
                if (this.isFinished) return !1;
                this.flush();
                try {
                    this.emit("end"), this.cleanUp(), this.isFinished = !0
                } catch (e) {
                    this.emit("error", e)
                }
                return !0
            }, error: function (e) {
                return !this.isFinished && (this.isPaused ? this.generatedError = e : (this.isFinished = !0, this.emit("error", e), this.previous && this.previous.error(e), this.cleanUp()), !0)
            }, on: function (e, t) {
                return this._listeners[e].push(t), this
            }, cleanUp: function () {
                this.streamInfo = this.generatedError = this.extraStreamInfo = null, this._listeners = []
            }, emit: function (e, t) {
                if (this._listeners[e]) for (var r = 0; r < this._listeners[e].length; r++) this._listeners[e][r].call(this, t)
            }, pipe: function (e) {
                return e.registerPrevious(this)
            }, registerPrevious: function (e) {
                if (this.isLocked) throw new Error("The stream '" + this + "' has already been used.");
                this.streamInfo = e.streamInfo, this.mergeStreamInfo(), this.previous = e;
                var t = this;
                return e.on("data", (function (e) {
                    t.processChunk(e)
                })), e.on("end", (function () {
                    t.end()
                })), e.on("error", (function (e) {
                    t.error(e)
                })), this
            }, pause: function () {
                return !this.isPaused && !this.isFinished && (this.isPaused = !0, this.previous && this.previous.pause(), !0)
            }, resume: function () {
                if (!this.isPaused || this.isFinished) return !1;
                this.isPaused = !1;
                var e = !1;
                return this.generatedError && (this.error(this.generatedError), e = !0), this.previous && this.previous.resume(), !e
            }, flush: function () {
            }, processChunk: function (e) {
                this.push(e)
            }, withStreamInfo: function (e, t) {
                return this.extraStreamInfo[e] = t, this.mergeStreamInfo(), this
            }, mergeStreamInfo: function () {
                for (var e in this.extraStreamInfo) this.extraStreamInfo.hasOwnProperty(e) && (this.streamInfo[e] = this.extraStreamInfo[e])
            }, lock: function () {
                if (this.isLocked) throw new Error("The stream '" + this + "' has already been used.");
                this.isLocked = !0, this.previous && this.previous.lock()
            }, toString: function () {
                var e = "Worker " + this.name;
                return this.previous ? this.previous + " -> " + e : e
            }
        }, e.exports = r
    })), n = e((function (e, t) {
        "use strict";
        var r = a({}), n = o({}), i = s({});

        function h(e) {
            return e
        }

        function u(e, t) {
            for (var r = 0; r < e.length; ++r) t[r] = 255 & e.charCodeAt(r);
            return t
        }

        t.newBlob = function (e, r) {
            t.checkSupport("blob");
            try {
                return new Blob([e], {type: r})
            } catch (i) {
                try {
                    var n = new (self.BlobBuilder || self.WebKitBlobBuilder || self.MozBlobBuilder || self.MSBlobBuilder);
                    return n.append(e), n.getBlob(r)
                } catch (i) {
                    throw new Error("Bug : can't construct the Blob.")
                }
            }
        };
        var c = {
            stringifyByChunk: function (e, t, r) {
                var n = [], i = 0, s = e.length;
                if (s <= r) return String.fromCharCode.apply(null, e);
                for (; i < s;) "array" === t || "nodebuffer" === t ? n.push(String.fromCharCode.apply(null, e.slice(i, Math.min(i + r, s)))) : n.push(String.fromCharCode.apply(null, e.subarray(i, Math.min(i + r, s)))), i += r;
                return n.join("")
            }, stringifyByChar: function (e) {
                for (var t = "", r = 0; r < e.length; r++) t += String.fromCharCode(e[r]);
                return t
            }, applyCanBeUsed: {
                uint8array: function () {
                    try {
                        return r.uint8array && 1 === String.fromCharCode.apply(null, new Uint8Array(1)).length
                    } catch (e) {
                        return !1
                    }
                }(), nodebuffer: function () {
                    try {
                        return r.nodebuffer && 1 === String.fromCharCode.apply(null, i.allocBuffer(1)).length
                    } catch (e) {
                        return !1
                    }
                }()
            }
        };

        function d(e) {
            var r = 65536, n = t.getTypeOf(e), i = !0;
            if ("uint8array" === n ? i = c.applyCanBeUsed.uint8array : "nodebuffer" === n && (i = c.applyCanBeUsed.nodebuffer), i) for (; r > 1;) try {
                return c.stringifyByChunk(e, n, r)
            } catch (s) {
                r = Math.floor(r / 2)
            }
            return c.stringifyByChar(e)
        }

        function l(e, t) {
            for (var r = 0; r < e.length; r++) t[r] = e[r];
            return t
        }

        t.applyFromCharCode = d;
        var f = {};
        f.string = {
            string: h, array: function (e) {
                return u(e, new Array(e.length))
            }, arraybuffer: function (e) {
                return f.string.uint8array(e).buffer
            }, uint8array: function (e) {
                return u(e, new Uint8Array(e.length))
            }, nodebuffer: function (e) {
                return u(e, i.allocBuffer(e.length))
            }
        }, f.array = {
            string: d, array: h, arraybuffer: function (e) {
                return new Uint8Array(e).buffer
            }, uint8array: function (e) {
                return new Uint8Array(e)
            }, nodebuffer: function (e) {
                return i.newBufferFrom(e)
            }
        }, f.arraybuffer = {
            string: function (e) {
                return d(new Uint8Array(e))
            }, array: function (e) {
                return l(new Uint8Array(e), new Array(e.byteLength))
            }, arraybuffer: h, uint8array: function (e) {
                return new Uint8Array(e)
            }, nodebuffer: function (e) {
                return i.newBufferFrom(new Uint8Array(e))
            }
        }, f.uint8array = {
            string: d, array: function (e) {
                return l(e, new Array(e.length))
            }, arraybuffer: function (e) {
                return e.buffer
            }, uint8array: h, nodebuffer: function (e) {
                return i.newBufferFrom(e)
            }
        }, f.nodebuffer = {
            string: d, array: function (e) {
                return l(e, new Array(e.length))
            }, arraybuffer: function (e) {
                return f.nodebuffer.uint8array(e).buffer
            }, uint8array: function (e) {
                return l(e, new Uint8Array(e.length))
            }, nodebuffer: h
        }, t.transformTo = function (e, r) {
            if (r || (r = ""), !e) return r;
            t.checkSupport(e);
            var n = t.getTypeOf(r);
            return f[n][e](r)
        }, t.getTypeOf = function (e) {
            return "string" == typeof e ? "string" : "[object Array]" === Object.prototype.toString.call(e) ? "array" : r.nodebuffer && i.isBuffer(e) ? "nodebuffer" : r.uint8array && e instanceof Uint8Array ? "uint8array" : r.arraybuffer && e instanceof ArrayBuffer ? "arraybuffer" : void 0
        }, t.checkSupport = function (e) {
            if (!r[e.toLowerCase()]) throw new Error(e + " is not supported by this platform")
        }, t.MAX_VALUE_16BITS = 65535, t.MAX_VALUE_32BITS = -1, t.pretty = function (e) {
            var t, r, n = "";
            for (r = 0; r < (e || "").length; r++) n += "\\x" + ((t = e.charCodeAt(r)) < 16 ? "0" : "") + t.toString(16).toUpperCase();
            return n
        }, t.delay = function (e, t, r) {
            oo((function () {
                e.apply(r || null, t || [])
            }))
        }, t.inherits = function (e, t) {
            var r = function () {
            };
            r.prototype = t.prototype, e.prototype = new r
        }, t.extend = function () {
            var e, t, r = {};
            for (e = 0; e < arguments.length; e++) for (t in arguments[e]) arguments[e].hasOwnProperty(t) && void 0 === r[t] && (r[t] = arguments[e][t]);
            return r
        }, t.prepareContent = function (e, i, s, o, a) {
            return wo.Promise.resolve(i).then((function (e) {
                return r.blob && (e instanceof Blob || -1 !== ["[object File]", "[object Blob]"].indexOf(Object.prototype.toString.call(e))) && "undefined" != typeof FileReader ? new wo.Promise((function (t, r) {
                    var n = new FileReader;
                    n.onload = function (e) {
                        t(e.target.result)
                    }, n.onerror = function (e) {
                        r(e.target.error)
                    }, n.readAsArrayBuffer(e)
                })) : e
            })).then((function (i) {
                var h, c = t.getTypeOf(i);
                return c ? ("arraybuffer" === c ? i = t.transformTo("uint8array", i) : "string" === c && (a ? i = n.decode(i) : s && !0 !== o && (i = u(h = i, r.uint8array ? new Uint8Array(h.length) : new Array(h.length)))), i) : wo.Promise.reject(new Error("Can't read the data of '" + e + "'. Is it in a supported JavaScript type (String, Blob, ArrayBuffer, etc) ?"))
            }))
        }
    })), i = e((function (e, t) {
        (function (e, r) {
            (function () {
                var n = _e.nextTick, i = (Function.prototype.apply, Array.prototype.slice), s = {}, o = 0;

                function a(e, t) {
                    this._id = e, this._clearFn = t
                }

                a.prototype.unref = a.prototype.ref = function () {
                }, a.prototype.close = function () {
                    this._clearFn.call(window, this._id)
                }, t.setImmediate = "function" == typeof e ? e : function (e) {
                    var r = o++, a = !(arguments.length < 2) && i.call(arguments, 1);
                    return s[r] = !0, n((function () {
                        s[r] && (a ? e.apply(null, a) : e.call(null), t.clearImmediate(r))
                    })), r
                }, t.clearImmediate = "function" == typeof r ? r : function (e) {
                    delete s[e]
                }
            }).call(this)
        }).call(this, i({}).setImmediate, i({}).clearImmediate)
    })), s = e((function (e, t) {
        (function (t) {
            (function () {
                "use strict";
                e.exports = {
                    isNode: void 0 !== t, newBufferFrom: function (e, r) {
                        if (t.from && t.from !== Uint8Array.from) return t.from(e, r);
                        if ("number" == typeof e) throw new Error('The "data" argument must not be a number');
                        return new t(e, r)
                    }, allocBuffer: function (e) {
                        if (t.alloc) return t.alloc(e);
                        var r = new t(e);
                        return r.fill(0), r
                    }, isBuffer: function (e) {
                        return t.isBuffer(e)
                    }, isStream: function (e) {
                        return e && "function" == typeof e.on && "function" == typeof e.pause && "function" == typeof e.resume
                    }
                }
            }).call(this)
        }).call(this, y({}).Buffer)
    })), o = e((function (e, t) {
        "use strict";
        var r = n({}), i = a({}), s = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        t.encode = function (e) {
            for (var t, n, i, o, a, h, u, c = [], d = 0, l = e.length, f = l, p = "string" !== r.getTypeOf(e); d < e.length;) f = l - d, p ? (t = e[d++], n = d < l ? e[d++] : 0, i = d < l ? e[d++] : 0) : (t = e.charCodeAt(d++), n = d < l ? e.charCodeAt(d++) : 0, i = d < l ? e.charCodeAt(d++) : 0), o = t >> 2, a = (3 & t) << 4 | n >> 4, h = f > 1 ? (15 & n) << 2 | i >> 6 : 64, u = f > 2 ? 63 & i : 64, c.push(s.charAt(o) + s.charAt(a) + s.charAt(h) + s.charAt(u));
            return c.join("")
        }, t.decode = function (e) {
            var t, r, n, o, a, h, u = 0, c = 0;
            if ("data:" === e.substr(0, "data:".length)) throw new Error("Invalid base64 input, it looks like a data url.");
            var d, l = 3 * (e = e.replace(/[^A-Za-z0-9\+\/\=]/g, "")).length / 4;
            if (e.charAt(e.length - 1) === s.charAt(64) && l--, e.charAt(e.length - 2) === s.charAt(64) && l--, l % 1 != 0) throw new Error("Invalid base64 input, bad content length.");
            for (d = i.uint8array ? new Uint8Array(0 | l) : new Array(0 | l); u < e.length;) t = s.indexOf(e.charAt(u++)) << 2 | (o = s.indexOf(e.charAt(u++))) >> 4, r = (15 & o) << 4 | (a = s.indexOf(e.charAt(u++))) >> 2, n = (3 & a) << 6 | (h = s.indexOf(e.charAt(u++))), d[c++] = t, 64 !== a && (d[c++] = r), 64 !== h && (d[c++] = n);
            return d
        }
    })), a = e((function (e, t) {
        (function (e) {
            (function () {
                "use strict";
                if (t.base64 = !0, t.array = !0, t.string = !0, t.arraybuffer = "undefined" != typeof ArrayBuffer && "undefined" != typeof Uint8Array, t.nodebuffer = void 0 !== e, t.uint8array = "undefined" != typeof Uint8Array, "undefined" == typeof ArrayBuffer) t.blob = !1; else {
                    var r = new ArrayBuffer(0);
                    try {
                        t.blob = 0 === new Blob([r], {type: "application/zip"}).size
                    } catch (i) {
                        try {
                            var n = new (self.BlobBuilder || self.WebKitBlobBuilder || self.MozBlobBuilder || self.MSBlobBuilder);
                            n.append(r), t.blob = 0 === n.getBlob("application/zip").size
                        } catch (i) {
                            t.blob = !1
                        }
                    }
                }
                try {
                    t.nodestream = !!so.Readable
                } catch (i) {
                    t.nodestream = !1
                }
            }).call(this)
        }).call(this, y({}).Buffer)
    })), h = e((function (e, t) {
        (function (e) {
            (function () {
                var r = u({}), n = t, i = {
                    moov: ["mvhd", "meta", "traks", "mvex"],
                    trak: ["tkhd", "tref", "trgr", "edts", "meta", "mdia", "udta"],
                    edts: ["elst"],
                    mdia: ["mdhd", "hdlr", "elng", "minf"],
                    minf: ["vmhd", "smhd", "hmhd", "sthd", "nmhd", "dinf", "stbl"],
                    dinf: ["dref"],
                    stbl: ["stsd", "stts", "ctts", "cslg", "stsc", "stsz", "stz2", "stco", "co64", "stss", "stsh", "padb", "stdp", "sdtp", "sbgps", "sgpds", "subss", "saizs", "saios"],
                    mvex: ["mehd", "trexs", "leva"],
                    moof: ["mfhd", "meta", "trafs"],
                    traf: ["tfhd", "tfdt", "trun", "sbgps", "sgpds", "subss", "saizs", "saios", "meta"]
                };
                n.encode = function (t, r, i) {
                    return n.encodingLength(t), i = i || 0, r = r || e.alloc(t.length), n._encode(t, r, i)
                }, n._encode = function (e, t, s) {
                    var o = e.type, a = e.length;
                    a > 4294967295 && (a = 1), t.writeUInt32BE(a, s), t.write(e.type, s + 4, 4, "ascii");
                    var h = s + 8;
                    if (1 === a && (ms.encode(e.length, t, h), h += 8), r.fullBoxes[o] && (t.writeUInt32BE(e.flags || 0, h), t.writeUInt8(e.version || 0, h), h += 4), i[o]) i[o].forEach((function (r) {
                        if (5 === r.length) {
                            var i = e[r] || [];
                            r = r.substr(0, 4), i.forEach((function (e) {
                                n._encode(e, t, h), h += n.encode.bytes
                            }))
                        } else e[r] && (n._encode(e[r], t, h), h += n.encode.bytes)
                    })), e.otherBoxes && e.otherBoxes.forEach((function (e) {
                        n._encode(e, t, h), h += n.encode.bytes
                    })); else if (r[o]) {
                        var u = r[o].encode;
                        u(e, t, h), h += u.bytes
                    } else {
                        if (!e.buffer) throw new Error("Either `type` must be set to a known type (not'" + o + "') or `buffer` must be set");
                        e.buffer.copy(t, h), h += e.buffer.length
                    }
                    return n.encode.bytes = h - s, t
                }, n.readHeaders = function (e, t, n) {
                    if (t = t || 0, (n = n || e.length) - t < 8) return 8;
                    var i, s, o = e.readUInt32BE(t), a = e.toString("ascii", t + 4, t + 8), h = t + 8;
                    if (1 === o) {
                        if (n - t < 16) return 16;
                        o = ms.decode(e, h), h += 8
                    }
                    return r.fullBoxes[a] && (i = e.readUInt8(h), s = 16777215 & e.readUInt32BE(h), h += 4), {
                        length: o,
                        headersLen: h - t,
                        contentLen: o - (h - t),
                        type: a,
                        version: i,
                        flags: s
                    }
                }, n.decode = function (e, t, r) {
                    t = t || 0, r = r || e.length;
                    var i = n.readHeaders(e, t, r);
                    if (!i || i.length > r - t) throw new Error("Data too short");
                    return n.decodeWithoutHeaders(i, e, t + i.headersLen, t + i.length)
                }, n.decodeWithoutHeaders = function (t, s, o, a) {
                    o = o || 0, a = a || s.length;
                    var h = t.type, u = {};
                    if (i[h]) {
                        u.otherBoxes = [];
                        for (var c = i[h], d = o; a - d >= 8;) {
                            var l = n.decode(s, d, a);
                            if (d += l.length, c.indexOf(l.type) >= 0) u[l.type] = l; else if (c.indexOf(l.type + "s") >= 0) {
                                var f = l.type + "s";
                                (u[f] = u[f] || []).push(l)
                            } else u.otherBoxes.push(l)
                        }
                    } else r[h] ? u = (0, r[h].decode)(s, o, a) : u.buffer = e.from(s.slice(o, a));
                    return u.length = t.length, u.contentLen = t.contentLen, u.type = t.type, u.version = t.version, u.flags = t.flags, u
                }, n.encodingLength = function (e) {
                    var t = e.type, s = 8;
                    if (r.fullBoxes[t] && (s += 4), i[t]) i[t].forEach((function (t) {
                        if (5 === t.length) {
                            var r = e[t] || [];
                            t = t.substr(0, 4), r.forEach((function (e) {
                                e.type = t, s += n.encodingLength(e)
                            }))
                        } else if (e[t]) {
                            var i = e[t];
                            i.type = t, s += n.encodingLength(i)
                        }
                    })), e.otherBoxes && e.otherBoxes.forEach((function (e) {
                        s += n.encodingLength(e)
                    })); else if (r[t]) s += r[t].encodingLength(e); else {
                        if (!e.buffer) throw new Error("Either `type` must be set to a known type (not'" + t + "') or `buffer` must be set");
                        s += e.buffer.length
                    }
                    return s > 4294967295 && (s += 8), e.length = s, s
                }
            }).call(this)
        }).call(this, y({}).Buffer)
    })), u = e((function (e, t) {
        (function (e) {
            (function () {
                var r = h({});

                function n(e, t, r) {
                    for (var n = t; n < r; n++) e[n] = 0
                }

                function i(e, t, r) {
                    t.writeUInt32BE(Math.floor((e.getTime() + 20828448e5) / 1e3), r)
                }

                function s(e, t, r) {
                    t.writeUIntBE(Math.floor((e.getTime() + 20828448e5) / 1e3), r, 6)
                }

                function o(e, t, r) {
                    t.writeUInt16BE(Math.floor(e) % 65536, r), t.writeUInt16BE(Math.floor(256 * e * 256) % 65536, r + 2)
                }

                function a(e, t, r) {
                    e || (e = [0, 0, 0, 0, 0, 0, 0, 0, 0]);
                    for (var n = 0; n < e.length; n++) o(e[n], t, r + 4 * n)
                }

                function u(e) {
                    for (var t = new Array(e.length / 4), r = 0; r < t.length; r++) t[r] = l(e, 4 * r);
                    return t
                }

                function c(e, t) {
                    return new Date(1e3 * e.readUIntBE(t, 6) - 20828448e5)
                }

                function d(e, t) {
                    return new Date(1e3 * e.readUInt32BE(t) - 20828448e5)
                }

                function l(e, t) {
                    return e.readUInt16BE(t) + e.readUInt16BE(t + 2) / 65536
                }

                function f(e, t) {
                    return e[t] + e[t + 1] / 256
                }

                function p(e, t, r) {
                    var n;
                    for (n = 0; n < r && 0 !== e[t + n]; n++) ;
                    return e.toString("utf8", t, t + n)
                }

                t.fullBoxes = {}, ["mvhd", "tkhd", "mdhd", "vmhd", "smhd", "stsd", "esds", "stsz", "stco", "co64", "stss", "stts", "ctts", "stsc", "dref", "elst", "hdlr", "mehd", "trex", "mfhd", "tfhd", "tfdt", "trun"].forEach((function (e) {
                    t.fullBoxes[e] = !0
                })), t.ftyp = {}, t.ftyp.encode = function (r, n, i) {
                    n = n ? n.slice(i) : e.alloc(t.ftyp.encodingLength(r));
                    var s = r.compatibleBrands || [];
                    n.write(r.brand, 0, 4, "ascii"), n.writeUInt32BE(r.brandVersion, 4);
                    for (var o = 0; o < s.length; o++) n.write(s[o], 8 + 4 * o, 4, "ascii");
                    return t.ftyp.encode.bytes = 8 + 4 * s.length, n
                }, t.ftyp.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).toString("ascii", 0, 4), n = e.readUInt32BE(4), i = [], s = 8; s < e.length; s += 4) i.push(e.toString("ascii", s, s + 4));
                    return {brand: r, brandVersion: n, compatibleBrands: i}
                }, t.ftyp.encodingLength = function (e) {
                    return 8 + 4 * (e.compatibleBrands || []).length
                }, t.mvhd = {}, t.mvhd.encode = function (r, s, h) {
                    return s = s ? s.slice(h) : e.alloc(96), i(r.ctime || new Date, s, 0), i(r.mtime || new Date, s, 4), s.writeUInt32BE(r.timeScale || 0, 8), s.writeUInt32BE(r.duration || 0, 12), o(r.preferredRate || 0, s, 16), function (e, t, r) {
                        t[20] = Math.floor(e) % 256, t[21] = Math.floor(256 * e) % 256
                    }(r.preferredVolume || 0, s), n(s, 22, 32), a(r.matrix, s, 32), s.writeUInt32BE(r.previewTime || 0, 68), s.writeUInt32BE(r.previewDuration || 0, 72), s.writeUInt32BE(r.posterTime || 0, 76), s.writeUInt32BE(r.selectionTime || 0, 80), s.writeUInt32BE(r.selectionDuration || 0, 84), s.writeUInt32BE(r.currentTime || 0, 88), s.writeUInt32BE(r.nextTrackId || 0, 92), t.mvhd.encode.bytes = 96, s
                }, t.mvhd.decode = function (e, t) {
                    return {
                        ctime: d(e = e.slice(t), 0),
                        mtime: d(e, 4),
                        timeScale: e.readUInt32BE(8),
                        duration: e.readUInt32BE(12),
                        preferredRate: l(e, 16),
                        preferredVolume: f(e, 20),
                        matrix: u(e.slice(32, 68)),
                        previewTime: e.readUInt32BE(68),
                        previewDuration: e.readUInt32BE(72),
                        posterTime: e.readUInt32BE(76),
                        selectionTime: e.readUInt32BE(80),
                        selectionDuration: e.readUInt32BE(84),
                        currentTime: e.readUInt32BE(88),
                        nextTrackId: e.readUInt32BE(92)
                    }
                }, t.mvhd.encodingLength = function (e) {
                    return 96
                }, t.tkhd = {}, t.tkhd.encode = function (r, s, o) {
                    return s = s ? s.slice(o) : e.alloc(80), i(r.ctime || new Date, s, 0), i(r.mtime || new Date, s, 4), s.writeUInt32BE(r.trackId || 0, 8), n(s, 12, 16), s.writeUInt32BE(r.duration || 0, 16), n(s, 20, 28), s.writeUInt16BE(r.layer || 0, 28), s.writeUInt16BE(r.alternateGroup || 0, 30), s.writeUInt16BE(r.volume || 0, 32), a(r.matrix, s, 36), s.writeUInt32BE(r.trackWidth || 0, 72), s.writeUInt32BE(r.trackHeight || 0, 76), t.tkhd.encode.bytes = 80, s
                }, t.tkhd.decode = function (e, t) {
                    return {
                        ctime: d(e = e.slice(t), 0),
                        mtime: d(e, 4),
                        trackId: e.readUInt32BE(8),
                        duration: e.readUInt32BE(16),
                        layer: e.readUInt16BE(28),
                        alternateGroup: e.readUInt16BE(30),
                        volume: e.readUInt16BE(32),
                        matrix: u(e.slice(36, 72)),
                        trackWidth: e.readUInt32BE(72),
                        trackHeight: e.readUInt32BE(76)
                    }
                }, t.tkhd.encodingLength = function (e) {
                    return 80
                }, t.mdhd = {}, t.mdhd.encode = function (r, n, o) {
                    return 1 === r.version ? (n = n ? n.slice(o) : e.alloc(32), s(r.ctime || new Date, n, 0), s(r.mtime || new Date, n, 8), n.writeUInt32BE(r.timeScale || 0, 16), n.writeUIntBE(r.duration || 0, 20, 6), n.writeUInt16BE(r.language || 0, 28), n.writeUInt16BE(r.quality || 0, 30), t.mdhd.encode.bytes = 32, n) : (n = n ? n.slice(o) : e.alloc(20), i(r.ctime || new Date, n, 0), i(r.mtime || new Date, n, 4), n.writeUInt32BE(r.timeScale || 0, 8), n.writeUInt32BE(r.duration || 0, 12), n.writeUInt16BE(r.language || 0, 16), n.writeUInt16BE(r.quality || 0, 18), t.mdhd.encode.bytes = 20, n)
                }, t.mdhd.decode = function (e, t, r) {
                    return e = e.slice(t), r - t != 20 ? {
                        ctime: c(e, 0),
                        mtime: c(e, 8),
                        timeScale: e.readUInt32BE(16),
                        duration: e.readUIntBE(20, 6),
                        language: e.readUInt16BE(28),
                        quality: e.readUInt16BE(30)
                    } : {
                        ctime: d(e, 0),
                        mtime: d(e, 4),
                        timeScale: e.readUInt32BE(8),
                        duration: e.readUInt32BE(12),
                        language: e.readUInt16BE(16),
                        quality: e.readUInt16BE(18)
                    }
                }, t.mdhd.encodingLength = function (e) {
                    return 1 === e.version ? 32 : 20
                }, t.vmhd = {}, t.vmhd.encode = function (r, n, i) {
                    (n = n ? n.slice(i) : e.alloc(8)).writeUInt16BE(r.graphicsMode || 0, 0);
                    var s = r.opcolor || [0, 0, 0];
                    return n.writeUInt16BE(s[0], 2), n.writeUInt16BE(s[1], 4), n.writeUInt16BE(s[2], 6), t.vmhd.encode.bytes = 8, n
                }, t.vmhd.decode = function (e, t) {
                    return {
                        graphicsMode: (e = e.slice(t)).readUInt16BE(0),
                        opcolor: [e.readUInt16BE(2), e.readUInt16BE(4), e.readUInt16BE(6)]
                    }
                }, t.vmhd.encodingLength = function (e) {
                    return 8
                }, t.smhd = {}, t.smhd.encode = function (r, i, s) {
                    return (i = i ? i.slice(s) : e.alloc(4)).writeUInt16BE(r.balance || 0, 0), n(i, 2, 4), t.smhd.encode.bytes = 4, i
                }, t.smhd.decode = function (e, t) {
                    return {balance: (e = e.slice(t)).readUInt16BE(0)}
                }, t.smhd.encodingLength = function (e) {
                    return 4
                }, t.stsd = {}, t.stsd.encode = function (n, i, s) {
                    i = i ? i.slice(s) : e.alloc(t.stsd.encodingLength(n));
                    var o = n.entries || [];
                    i.writeUInt32BE(o.length, 0);
                    for (var a = 4, h = 0; h < o.length; h++) {
                        var u = o[h];
                        r.encode(u, i, a), a += r.encode.bytes
                    }
                    return t.stsd.encode.bytes = a, i
                }, t.stsd.decode = function (e, t, n) {
                    for (var i = (e = e.slice(t)).readUInt32BE(0), s = new Array(i), o = 4, a = 0; a < i; a++) {
                        var h = r.decode(e, o, n);
                        s[a] = h, o += h.length
                    }
                    return {entries: s}
                }, t.stsd.encodingLength = function (e) {
                    var t = 4;
                    if (!e.entries) return t;
                    for (var n = 0; n < e.entries.length; n++) t += r.encodingLength(e.entries[n]);
                    return t
                }, t.avc1 = t.VisualSampleEntry = {}, t.VisualSampleEntry.encode = function (i, s, o) {
                    n(s = s ? s.slice(o) : e.alloc(t.VisualSampleEntry.encodingLength(i)), 0, 6), s.writeUInt16BE(i.dataReferenceIndex || 0, 6), n(s, 8, 24), s.writeUInt16BE(i.width || 0, 24), s.writeUInt16BE(i.height || 0, 26), s.writeUInt32BE(i.hResolution || 4718592, 28), s.writeUInt32BE(i.vResolution || 4718592, 32), n(s, 36, 40), s.writeUInt16BE(i.frameCount || 1, 40);
                    var a = i.compressorName || "", h = Math.min(a.length, 31);
                    s.writeUInt8(h, 42), s.write(a, 43, h, "utf8"), s.writeUInt16BE(i.depth || 24, 74), s.writeInt16BE(-1, 76);
                    var u = 78;
                    (i.children || []).forEach((function (e) {
                        r.encode(e, s, u), u += r.encode.bytes
                    })), t.VisualSampleEntry.encode.bytes = u
                }, t.VisualSampleEntry.decode = function (e, t, n) {
                    e = e.slice(t);
                    for (var i = n - t, s = Math.min(e.readUInt8(42), 31), o = {
                        dataReferenceIndex: e.readUInt16BE(6),
                        width: e.readUInt16BE(24),
                        height: e.readUInt16BE(26),
                        hResolution: e.readUInt32BE(28),
                        vResolution: e.readUInt32BE(32),
                        frameCount: e.readUInt16BE(40),
                        compressorName: e.toString("utf8", 43, 43 + s),
                        depth: e.readUInt16BE(74),
                        children: []
                    }, a = 78; i - a >= 8;) {
                        var h = r.decode(e, a, i);
                        o.children.push(h), o[h.type] = h, a += h.length
                    }
                    return o
                }, t.VisualSampleEntry.encodingLength = function (e) {
                    var t = 78;
                    return (e.children || []).forEach((function (e) {
                        t += r.encodingLength(e)
                    })), t
                }, t.avcC = {}, t.avcC.encode = function (r, n, i) {
                    n = n ? n.slice(i) : e.alloc(r.buffer.length), r.buffer.copy(n), t.avcC.encode.bytes = r.buffer.length
                }, t.avcC.decode = function (t, r, n) {
                    return {mimeCodec: (t = t.slice(r, n)).toString("hex", 1, 4), buffer: e.from(t)}
                }, t.avcC.encodingLength = function (e) {
                    return e.buffer.length
                }, t.mp4a = t.AudioSampleEntry = {}, t.AudioSampleEntry.encode = function (i, s, o) {
                    n(s = s ? s.slice(o) : e.alloc(t.AudioSampleEntry.encodingLength(i)), 0, 6), s.writeUInt16BE(i.dataReferenceIndex || 0, 6), n(s, 8, 16), s.writeUInt16BE(i.channelCount || 2, 16), s.writeUInt16BE(i.sampleSize || 16, 18), n(s, 20, 24), s.writeUInt32BE(i.sampleRate || 0, 24);
                    var a = 28;
                    (i.children || []).forEach((function (e) {
                        r.encode(e, s, a), a += r.encode.bytes
                    })), t.AudioSampleEntry.encode.bytes = a
                }, t.AudioSampleEntry.decode = function (e, t, n) {
                    for (var i = n - t, s = {
                        dataReferenceIndex: (e = e.slice(t, n)).readUInt16BE(6),
                        channelCount: e.readUInt16BE(16),
                        sampleSize: e.readUInt16BE(18),
                        sampleRate: e.readUInt32BE(24),
                        children: []
                    }, o = 28; i - o >= 8;) {
                        var a = r.decode(e, o, i);
                        s.children.push(a), s[a.type] = a, o += a.length
                    }
                    return s
                }, t.AudioSampleEntry.encodingLength = function (e) {
                    var t = 28;
                    return (e.children || []).forEach((function (e) {
                        t += r.encodingLength(e)
                    })), t
                }, t.esds = {}, t.esds.encode = function (r, n, i) {
                    n = n ? n.slice(i) : e.alloc(r.buffer.length), r.buffer.copy(n, 0), t.esds.encode.bytes = r.buffer.length
                }, t.esds.decode = function (t, r, n) {
                    t = t.slice(r, n);
                    var i = _s.Descriptor.decode(t, 0, t.length),
                        s = ("ESDescriptor" === i.tagName ? i : {}).DecoderConfigDescriptor || {}, o = s.oti || 0,
                        a = s.DecoderSpecificInfo, h = a ? (248 & a.buffer.readUInt8(0)) >> 3 : 0, u = null;
                    return o && (u = o.toString(16), h && (u += "." + h)), {mimeCodec: u, buffer: e.from(t.slice(0))}
                }, t.esds.encodingLength = function (e) {
                    return e.buffer.length
                }, t.stsz = {}, t.stsz.encode = function (r, n, i) {
                    var s = r.entries || [];
                    (n = n ? n.slice(i) : e.alloc(t.stsz.encodingLength(r))).writeUInt32BE(0, 0), n.writeUInt32BE(s.length, 4);
                    for (var o = 0; o < s.length; o++) n.writeUInt32BE(s[o], 4 * o + 8);
                    return t.stsz.encode.bytes = 8 + 4 * s.length, n
                }, t.stsz.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).readUInt32BE(0), n = e.readUInt32BE(4), i = new Array(n), s = 0; s < n; s++) i[s] = 0 === r ? e.readUInt32BE(4 * s + 8) : r;
                    return {entries: i}
                }, t.stsz.encodingLength = function (e) {
                    return 8 + 4 * e.entries.length
                }, t.stss = t.stco = {}, t.stco.encode = function (r, n, i) {
                    var s = r.entries || [];
                    (n = n ? n.slice(i) : e.alloc(t.stco.encodingLength(r))).writeUInt32BE(s.length, 0);
                    for (var o = 0; o < s.length; o++) n.writeUInt32BE(s[o], 4 * o + 4);
                    return t.stco.encode.bytes = 4 + 4 * s.length, n
                }, t.stco.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).readUInt32BE(0), n = new Array(r), i = 0; i < r; i++) n[i] = e.readUInt32BE(4 * i + 4);
                    return {entries: n}
                }, t.stco.encodingLength = function (e) {
                    return 4 + 4 * e.entries.length
                }, t.co64 = {}, t.co64.encode = function (r, n, i) {
                    var s = r.entries || [];
                    (n = n ? n.slice(i) : e.alloc(t.co64.encodingLength(r))).writeUInt32BE(s.length, 0);
                    for (var o = 0; o < s.length; o++) ms.encode(s[o], n, 8 * o + 4);
                    return t.co64.encode.bytes = 4 + 8 * s.length, n
                }, t.co64.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).readUInt32BE(0), n = new Array(r), i = 0; i < r; i++) n[i] = ms.decode(e, 8 * i + 4);
                    return {entries: n}
                }, t.co64.encodingLength = function (e) {
                    return 4 + 8 * e.entries.length
                }, t.stts = {}, t.stts.encode = function (r, n, i) {
                    var s = r.entries || [];
                    (n = n ? n.slice(i) : e.alloc(t.stts.encodingLength(r))).writeUInt32BE(s.length, 0);
                    for (var o = 0; o < s.length; o++) {
                        var a = 8 * o + 4;
                        n.writeUInt32BE(s[o].count || 0, a), n.writeUInt32BE(s[o].duration || 0, a + 4)
                    }
                    return t.stts.encode.bytes = 4 + 8 * r.entries.length, n
                }, t.stts.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).readUInt32BE(0), n = new Array(r), i = 0; i < r; i++) {
                        var s = 8 * i + 4;
                        n[i] = {count: e.readUInt32BE(s), duration: e.readUInt32BE(s + 4)}
                    }
                    return {entries: n}
                }, t.stts.encodingLength = function (e) {
                    return 4 + 8 * e.entries.length
                }, t.ctts = {}, t.ctts.encode = function (r, n, i) {
                    var s = r.entries || [];
                    (n = n ? n.slice(i) : e.alloc(t.ctts.encodingLength(r))).writeUInt32BE(s.length, 0);
                    for (var o = 0; o < s.length; o++) {
                        var a = 8 * o + 4;
                        n.writeUInt32BE(s[o].count || 0, a), n.writeUInt32BE(s[o].compositionOffset || 0, a + 4)
                    }
                    return t.ctts.encode.bytes = 4 + 8 * s.length, n
                }, t.ctts.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).readUInt32BE(0), n = new Array(r), i = 0; i < r; i++) {
                        var s = 8 * i + 4;
                        n[i] = {count: e.readUInt32BE(s), compositionOffset: e.readInt32BE(s + 4)}
                    }
                    return {entries: n}
                }, t.ctts.encodingLength = function (e) {
                    return 4 + 8 * e.entries.length
                }, t.stsc = {}, t.stsc.encode = function (r, n, i) {
                    var s = r.entries || [];
                    (n = n ? n.slice(i) : e.alloc(t.stsc.encodingLength(r))).writeUInt32BE(s.length, 0);
                    for (var o = 0; o < s.length; o++) {
                        var a = 12 * o + 4;
                        n.writeUInt32BE(s[o].firstChunk || 0, a), n.writeUInt32BE(s[o].samplesPerChunk || 0, a + 4), n.writeUInt32BE(s[o].sampleDescriptionId || 0, a + 8)
                    }
                    return t.stsc.encode.bytes = 4 + 12 * s.length, n
                }, t.stsc.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).readUInt32BE(0), n = new Array(r), i = 0; i < r; i++) {
                        var s = 12 * i + 4;
                        n[i] = {
                            firstChunk: e.readUInt32BE(s),
                            samplesPerChunk: e.readUInt32BE(s + 4),
                            sampleDescriptionId: e.readUInt32BE(s + 8)
                        }
                    }
                    return {entries: n}
                }, t.stsc.encodingLength = function (e) {
                    return 4 + 12 * e.entries.length
                }, t.dref = {}, t.dref.encode = function (r, n, i) {
                    n = n ? n.slice(i) : e.alloc(t.dref.encodingLength(r));
                    var s = r.entries || [];
                    n.writeUInt32BE(s.length, 0);
                    for (var o = 4, a = 0; a < s.length; a++) {
                        var h = s[a], u = (h.buf ? h.buf.length : 0) + 4 + 4;
                        n.writeUInt32BE(u, o), o += 4, n.write(h.type, o, 4, "ascii"), o += 4, h.buf && (h.buf.copy(n, o), o += h.buf.length)
                    }
                    return t.dref.encode.bytes = o, n
                }, t.dref.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).readUInt32BE(0), n = new Array(r), i = 4, s = 0; s < r; s++) {
                        var o = e.readUInt32BE(i), a = e.toString("ascii", i + 4, i + 8), h = e.slice(i + 8, i + o);
                        i += o, n[s] = {type: a, buf: h}
                    }
                    return {entries: n}
                }, t.dref.encodingLength = function (e) {
                    var t = 4;
                    if (!e.entries) return t;
                    for (var r = 0; r < e.entries.length; r++) {
                        var n = e.entries[r].buf;
                        t += (n ? n.length : 0) + 4 + 4
                    }
                    return t
                }, t.elst = {}, t.elst.encode = function (r, n, i) {
                    var s = r.entries || [];
                    (n = n ? n.slice(i) : e.alloc(t.elst.encodingLength(r))).writeUInt32BE(s.length, 0);
                    for (var a = 0; a < s.length; a++) {
                        var h = 12 * a + 4;
                        n.writeUInt32BE(s[a].trackDuration || 0, h), n.writeUInt32BE(s[a].mediaTime || 0, h + 4), o(s[a].mediaRate || 0, n, h + 8)
                    }
                    return t.elst.encode.bytes = 4 + 12 * s.length, n
                }, t.elst.decode = function (e, t) {
                    for (var r = (e = e.slice(t)).readUInt32BE(0), n = new Array(r), i = 0; i < r; i++) {
                        var s = 12 * i + 4;
                        n[i] = {
                            trackDuration: e.readUInt32BE(s),
                            mediaTime: e.readInt32BE(s + 4),
                            mediaRate: l(e, s + 8)
                        }
                    }
                    return {entries: n}
                }, t.elst.encodingLength = function (e) {
                    return 4 + 12 * e.entries.length
                }, t.hdlr = {}, t.hdlr.encode = function (r, n, i) {
                    n = n ? n.slice(i) : e.alloc(t.hdlr.encodingLength(r));
                    var s = 21 + (r.name || "").length;
                    return n.fill(0, 0, s), n.write(r.handlerType || "", 4, 4, "ascii"), function (t, r, n) {
                        var i = e.from(t, "utf8");
                        i.copy(r, 20), r[20 + i.length] = 0
                    }(r.name || "", n), t.hdlr.encode.bytes = s, n
                }, t.hdlr.decode = function (e, t, r) {
                    return {handlerType: (e = e.slice(t)).toString("ascii", 4, 8), name: p(e, 20, r)}
                }, t.hdlr.encodingLength = function (e) {
                    return 21 + (e.name || "").length
                }, t.mehd = {}, t.mehd.encode = function (r, n, i) {
                    return (n = n ? n.slice(i) : e.alloc(4)).writeUInt32BE(r.fragmentDuration || 0, 0), t.mehd.encode.bytes = 4, n
                }, t.mehd.decode = function (e, t) {
                    return {fragmentDuration: (e = e.slice(t)).readUInt32BE(0)}
                }, t.mehd.encodingLength = function (e) {
                    return 4
                }, t.trex = {}, t.trex.encode = function (r, n, i) {
                    return (n = n ? n.slice(i) : e.alloc(20)).writeUInt32BE(r.trackId || 0, 0), n.writeUInt32BE(r.defaultSampleDescriptionIndex || 0, 4), n.writeUInt32BE(r.defaultSampleDuration || 0, 8), n.writeUInt32BE(r.defaultSampleSize || 0, 12), n.writeUInt32BE(r.defaultSampleFlags || 0, 16), t.trex.encode.bytes = 20, n
                }, t.trex.decode = function (e, t) {
                    return {
                        trackId: (e = e.slice(t)).readUInt32BE(0),
                        defaultSampleDescriptionIndex: e.readUInt32BE(4),
                        defaultSampleDuration: e.readUInt32BE(8),
                        defaultSampleSize: e.readUInt32BE(12),
                        defaultSampleFlags: e.readUInt32BE(16)
                    }
                }, t.trex.encodingLength = function (e) {
                    return 20
                }, t.mfhd = {}, t.mfhd.encode = function (r, n, i) {
                    return (n = n ? n.slice(i) : e.alloc(4)).writeUInt32BE(r.sequenceNumber || 0, 0), t.mfhd.encode.bytes = 4, n
                }, t.mfhd.decode = function (e, t) {
                    return {sequenceNumber: e.readUInt32BE(0)}
                }, t.mfhd.encodingLength = function (e) {
                    return 4
                }, t.tfhd = {}, t.tfhd.encode = function (r, n, i) {
                    return (n = n ? n.slice(i) : e.alloc(4)).writeUInt32BE(r.trackId, 0), t.tfhd.encode.bytes = 4, n
                }, t.tfhd.decode = function (e, t) {
                }, t.tfhd.encodingLength = function (e) {
                    return 4
                }, t.tfdt = {}, t.tfdt.encode = function (r, n, i) {
                    return (n = n ? n.slice(i) : e.alloc(4)).writeUInt32BE(r.baseMediaDecodeTime || 0, 0), t.tfdt.encode.bytes = 4, n
                }, t.tfdt.decode = function (e, t) {
                },t.tfdt.encodingLength = function (e) {
                    return 4
                },t.trun = {},t.trun.encode = function (r, n, i) {
                    (n = n ? n.slice(i) : e.alloc(8 + 16 * r.entries.length)).writeUInt32BE(r.entries.length, 0), n.writeInt32BE(r.dataOffset, 4);
                    for (var s = 8, o = 0; o < r.entries.length; o++) {
                        var a = r.entries[o];
                        n.writeUInt32BE(a.sampleDuration, s), s += 4, n.writeUInt32BE(a.sampleSize, s), s += 4, n.writeUInt32BE(a.sampleFlags, s), s += 4, 0 === (r.version || 0) ? n.writeUInt32BE(a.sampleCompositionTimeOffset, s) : n.writeInt32BE(a.sampleCompositionTimeOffset, s), s += 4
                    }
                    t.trun.encode.bytes = s
                },t.trun.decode = function (e, t) {
                },t.trun.encodingLength = function (e) {
                    return 8 + 16 * e.entries.length
                },t.mdat = {},t.mdat.encode = function (e, r, n) {
                    e.buffer ? (e.buffer.copy(r, n), t.mdat.encode.bytes = e.buffer.length) : t.mdat.encode.bytes = t.mdat.encodingLength(e)
                },t.mdat.decode = function (t, r, n) {
                    return {buffer: e.from(t.slice(r, n))}
                },t.mdat.encodingLength = function (e) {
                    return e.buffer ? e.buffer.length : e.contentLength
                }
            }).call(this)
        }).call(this, y({}).Buffer)
    })), c = e((function (e, t) {
        var r = 1e3, n = 6e4, i = 60 * n, s = 24 * i;

        function o(e, t, r, n) {
            var i = t >= 1.5 * r;
            return Math.round(e / r) + " " + n + (i ? "s" : "")
        }

        e.exports = function (e, t) {
            t = t || {};
            var a, h, u = typeof e;
            if ("string" === u && e.length > 0) return function (e) {
                if (!((e = String(e)).length > 100)) {
                    var t = /^(-?(?:\d+)?\.?\d+) *(milliseconds?|msecs?|ms|seconds?|secs?|s|minutes?|mins?|m|hours?|hrs?|h|days?|d|weeks?|w|years?|yrs?|y)?$/i.exec(e);
                    if (t) {
                        var o = parseFloat(t[1]);
                        switch ((t[2] || "ms").toLowerCase()) {
                            case"years":
                            case"year":
                            case"yrs":
                            case"yr":
                            case"y":
                                return 315576e5 * o;
                            case"weeks":
                            case"week":
                            case"w":
                                return 6048e5 * o;
                            case"days":
                            case"day":
                            case"d":
                                return o * s;
                            case"hours":
                            case"hour":
                            case"hrs":
                            case"hr":
                            case"h":
                                return o * i;
                            case"minutes":
                            case"minute":
                            case"mins":
                            case"min":
                            case"m":
                                return o * n;
                            case"seconds":
                            case"second":
                            case"secs":
                            case"sec":
                            case"s":
                                return o * r;
                            case"milliseconds":
                            case"millisecond":
                            case"msecs":
                            case"msec":
                            case"ms":
                                return o;
                            default:
                                return
                        }
                    }
                }
            }(e);
            if ("number" === u && isFinite(e)) return t.long ? (a = e, (h = Math.abs(a)) >= s ? o(a, h, s, "day") : h >= i ? o(a, h, i, "hour") : h >= n ? o(a, h, n, "minute") : h >= r ? o(a, h, r, "second") : a + " ms") : function (e) {
                var t = Math.abs(e);
                return t >= s ? Math.round(e / s) + "d" : t >= i ? Math.round(e / i) + "h" : t >= n ? Math.round(e / n) + "m" : t >= r ? Math.round(e / r) + "s" : e + "ms"
            }(e);
            throw new Error("val is not a non-empty string or a valid number. val=" + JSON.stringify(e))
        }
    })), d = e((function (e, t) {
        (function (t, r) {
            (function () {
                "use strict";
                var n;
                e.exports = C, C.ReadableState = S, $.EventEmitter;
                var i, s = function (e, t) {
                    return e.listeners(t).length
                }, o = y({}).Buffer, a = r.Uint8Array || function () {
                };
                i = ae && ae.debuglog ? ae.debuglog("stream") : function () {
                };
                var h, u, c, d = Me.getHighWaterMark, p = Be.codes, _ = p.ERR_INVALID_ARG_TYPE,
                    b = p.ERR_STREAM_PUSH_AFTER_EOF, v = p.ERR_METHOD_NOT_IMPLEMENTED,
                    w = p.ERR_STREAM_UNSHIFT_AFTER_END_EVENT;
                De(C, oe);
                var E = Re.errorOrDestroy, k = ["error", "close", "destroy", "pause", "resume"];

                function S(e, t, r) {
                    n = n || g({}), e = e || {}, "boolean" != typeof r && (r = t instanceof n), this.objectMode = !!e.objectMode, r && (this.objectMode = this.objectMode || !!e.readableObjectMode), this.highWaterMark = d(this, e, "readableHighWaterMark", r), this.buffer = new ge, this.length = 0, this.pipes = null, this.pipesCount = 0, this.flowing = null, this.ended = !1, this.endEmitted = !1, this.reading = !1, this.sync = !0, this.needReadable = !1, this.emittedReadable = !1, this.readableListening = !1, this.resumeScheduled = !1, this.paused = !0, this.emitClose = !1 !== e.emitClose, this.autoDestroy = !!e.autoDestroy, this.destroyed = !1, this.defaultEncoding = e.defaultEncoding || "utf8", this.awaitDrain = 0, this.readingMore = !1, this.decoder = null, this.encoding = null, e.encoding && (h || (h = m({}).StringDecoder), this.decoder = new h(e.encoding), this.encoding = e.encoding)
                }

                function C(e) {
                    if (n = n || g({}), !(this instanceof C)) return new C(e);
                    var t = this instanceof n;
                    this._readableState = new S(e, this, t), this.readable = !0, e && ("function" == typeof e.read && (this._read = e.read), "function" == typeof e.destroy && (this._destroy = e.destroy)), oe.call(this)
                }

                function x(e, t, r, n, s) {
                    i("readableAddChunk", t);
                    var h, u = e._readableState;
                    if (null === t) u.reading = !1, function (e, t) {
                        if (i("onEofChunk"), !t.ended) {
                            if (t.decoder) {
                                var r = t.decoder.end();
                                r && r.length && (t.buffer.push(r), t.length += t.objectMode ? 1 : r.length)
                            }
                            t.ended = !0, t.sync ? I(e) : (t.needReadable = !1, t.emittedReadable || (t.emittedReadable = !0, R(e)))
                        }
                    }(e, u); else if (s || (h = function (e, t) {
                        var r, n;
                        return n = t, o.isBuffer(n) || n instanceof a || "string" == typeof t || void 0 === t || e.objectMode || (r = new _("chunk", ["string", "Buffer", "Uint8Array"], t)), r
                    }(u, t)), h) E(e, h); else if (u.objectMode || t && t.length > 0) if ("string" == typeof t || u.objectMode || Object.getPrototypeOf(t) === o.prototype || (t = function (e) {
                        return o.from(e)
                    }(t)), n) u.endEmitted ? E(e, new w) : A(e, u, t, !0); else if (u.ended) E(e, new b); else {
                        if (u.destroyed) return !1;
                        u.reading = !1, u.decoder && !r ? (t = u.decoder.write(t), u.objectMode || 0 !== t.length ? A(e, u, t, !1) : B(e, u)) : A(e, u, t, !1)
                    } else n || (u.reading = !1, B(e, u));
                    return !u.ended && (u.length < u.highWaterMark || 0 === u.length)
                }

                function A(e, t, r, n) {
                    t.flowing && 0 === t.length && !t.sync ? (t.awaitDrain = 0, e.emit("data", r)) : (t.length += t.objectMode ? 1 : r.length, n ? t.buffer.unshift(r) : t.buffer.push(r), t.needReadable && I(e)), B(e, t)
                }

                Object.defineProperty(C.prototype, "destroyed", {
                    enumerable: !1, get: function () {
                        return void 0 !== this._readableState && this._readableState.destroyed
                    }, set: function (e) {
                        this._readableState && (this._readableState.destroyed = e)
                    }
                }), C.prototype.destroy = Re.destroy, C.prototype._undestroy = Re.undestroy, C.prototype._destroy = function (e, t) {
                    t(e)
                }, C.prototype.push = function (e, t) {
                    var r, n = this._readableState;
                    return n.objectMode ? r = !0 : "string" == typeof e && ((t = t || n.defaultEncoding) !== n.encoding && (e = o.from(e, t), t = ""), r = !0), x(this, e, t, !1, r)
                }, C.prototype.unshift = function (e) {
                    return x(this, e, null, !0, !1)
                }, C.prototype.isPaused = function () {
                    return !1 === this._readableState.flowing
                }, C.prototype.setEncoding = function (e) {
                    h || (h = m({}).StringDecoder);
                    var t = new h(e);
                    this._readableState.decoder = t, this._readableState.encoding = this._readableState.decoder.encoding;
                    for (var r = this._readableState.buffer.head, n = ""; null !== r;) n += t.write(r.data), r = r.next;
                    return this._readableState.buffer.clear(), "" !== n && this._readableState.buffer.push(n), this._readableState.length = n.length, this
                };

                function T(e, t) {
                    return e <= 0 || 0 === t.length && t.ended ? 0 : t.objectMode ? 1 : e != e ? t.flowing && t.length ? t.buffer.head.data.length : t.length : (e > t.highWaterMark && (t.highWaterMark = function (e) {
                        return e >= 1073741824 ? e = 1073741824 : (e--, e |= e >>> 1, e |= e >>> 2, e |= e >>> 4, e |= e >>> 8, e |= e >>> 16, e++), e
                    }(e)), e <= t.length ? e : t.ended ? t.length : (t.needReadable = !0, 0))
                }

                function I(e) {
                    var r = e._readableState;
                    i("emitReadable", r.needReadable, r.emittedReadable), r.needReadable = !1, r.emittedReadable || (i("emitReadable", r.flowing), r.emittedReadable = !0, t.nextTick(R, e))
                }

                function R(e) {
                    var t = e._readableState;
                    i("emitReadable_", t.destroyed, t.length, t.ended), t.destroyed || !t.length && !t.ended || (e.emit("readable"), t.emittedReadable = !1), t.needReadable = !t.flowing && !t.ended && t.length <= t.highWaterMark, M(e)
                }

                function B(e, r) {
                    r.readingMore || (r.readingMore = !0, t.nextTick(L, e, r))
                }

                function L(e, t) {
                    for (; !t.reading && !t.ended && (t.length < t.highWaterMark || t.flowing && 0 === t.length);) {
                        var r = t.length;
                        if (i("maybeReadMore read 0"), e.read(0), r === t.length) break
                    }
                    t.readingMore = !1
                }

                function O(e) {
                    var t = e._readableState;
                    t.readableListening = e.listenerCount("readable") > 0, t.resumeScheduled && !t.paused ? t.flowing = !0 : e.listenerCount("data") > 0 && e.resume()
                }

                function U(e) {
                    i("readable nexttick read 0"), e.read(0)
                }

                function P(e, t) {
                    i("resume", t.reading), t.reading || e.read(0), t.resumeScheduled = !1, e.emit("resume"), M(e), t.flowing && !t.reading && e.read(0)
                }

                function M(e) {
                    var t = e._readableState;
                    for (i("flow", t.flowing); t.flowing && null !== e.read();) ;
                }

                function D(e, t) {
                    return 0 === t.length ? null : (t.objectMode ? r = t.buffer.shift() : !e || e >= t.length ? (r = t.decoder ? t.buffer.join("") : 1 === t.buffer.length ? t.buffer.first() : t.buffer.concat(t.length), t.buffer.clear()) : r = t.buffer.consume(e, t.decoder), r);
                    var r
                }

                function N(e) {
                    var r = e._readableState;
                    i("endReadable", r.endEmitted), r.endEmitted || (r.ended = !0, t.nextTick(j, r, e))
                }

                function j(e, t) {
                    if (i("endReadableNT", e.endEmitted, e.length), !e.endEmitted && 0 === e.length && (e.endEmitted = !0, t.readable = !1, t.emit("end"), e.autoDestroy)) {
                        var r = t._writableState;
                        (!r || r.autoDestroy && r.finished) && t.destroy()
                    }
                }

                function F(e, t) {
                    for (var r = 0, n = e.length; r < n; r++) if (e[r] === t) return r;
                    return -1
                }

                C.prototype.read = function (e) {
                    i("read", e), e = parseInt(e, 10);
                    var t = this._readableState, r = e;
                    if (0 !== e && (t.emittedReadable = !1), 0 === e && t.needReadable && ((0 !== t.highWaterMark ? t.length >= t.highWaterMark : t.length > 0) || t.ended)) return i("read: emitReadable", t.length, t.ended), 0 === t.length && t.ended ? N(this) : I(this), null;
                    if (0 === (e = T(e, t)) && t.ended) return 0 === t.length && N(this), null;
                    var n, s = t.needReadable;
                    return i("need readable", s), (0 === t.length || t.length - e < t.highWaterMark) && i("length less than watermark", s = !0), t.ended || t.reading ? i("reading or ended", s = !1) : s && (i("do read"), t.reading = !0, t.sync = !0, 0 === t.length && (t.needReadable = !0), this._read(t.highWaterMark), t.sync = !1, t.reading || (e = T(r, t))), null === (n = e > 0 ? D(e, t) : null) ? (t.needReadable = t.length <= t.highWaterMark, e = 0) : (t.length -= e, t.awaitDrain = 0), 0 === t.length && (t.ended || (t.needReadable = !0), r !== e && t.ended && N(this)), null !== n && this.emit("data", n), n
                }, C.prototype._read = function (e) {
                    E(this, new v("_read()"))
                }, C.prototype.pipe = function (e, r) {
                    var n = this, o = this._readableState;
                    switch (o.pipesCount) {
                        case 0:
                            o.pipes = e;
                            break;
                        case 1:
                            o.pipes = [o.pipes, e];
                            break;
                        default:
                            o.pipes.push(e)
                    }
                    o.pipesCount += 1, i("pipe count=%d opts=%j", o.pipesCount, r);
                    var a = r && !1 === r.end || e === t.stdout || e === t.stderr ? m : h;

                    function h() {
                        i("onend"), e.end()
                    }

                    o.endEmitted ? t.nextTick(a) : n.once("end", a), e.on("unpipe", (function t(r, s) {
                        i("onunpipe"), r === n && s && !1 === s.hasUnpiped && (s.hasUnpiped = !0, i("cleanup"), e.removeListener("close", f), e.removeListener("finish", p), e.removeListener("drain", u), e.removeListener("error", l), e.removeListener("unpipe", t), n.removeListener("end", h), n.removeListener("end", m), n.removeListener("data", d), c = !0, !o.awaitDrain || e._writableState && !e._writableState.needDrain || u())
                    }));
                    var u = function (e) {
                        return function () {
                            var t = e._readableState;
                            i("pipeOnDrain", t.awaitDrain), t.awaitDrain && t.awaitDrain--, 0 === t.awaitDrain && s(e, "data") && (t.flowing = !0, M(e))
                        }
                    }(n);
                    e.on("drain", u);
                    var c = !1;

                    function d(t) {
                        i("ondata");
                        var r = e.write(t);
                        i("dest.write", r), !1 === r && ((1 === o.pipesCount && o.pipes === e || o.pipesCount > 1 && -1 !== F(o.pipes, e)) && !c && (i("false write response, pause", o.awaitDrain), o.awaitDrain++), n.pause())
                    }

                    function l(t) {
                        i("onerror", t), m(), e.removeListener("error", l), 0 === s(e, "error") && E(e, t)
                    }

                    function f() {
                        e.removeListener("finish", p), m()
                    }

                    function p() {
                        i("onfinish"), e.removeListener("close", f), m()
                    }

                    function m() {
                        i("unpipe"), n.unpipe(e)
                    }

                    return n.on("data", d), function (e, t, r) {
                        if ("function" == typeof e.prependListener) return e.prependListener("error", r);
                        e._events && e._events.error ? Array.isArray(e._events.error) ? e._events.error.unshift(r) : e._events.error = [r, e._events.error] : e.on("error", r)
                    }(e, 0, l), e.once("close", f), e.once("finish", p), e.emit("pipe", n), o.flowing || (i("pipe resume"), n.resume()), e
                }, C.prototype.unpipe = function (e) {
                    var t = this._readableState, r = {hasUnpiped: !1};
                    if (0 === t.pipesCount) return this;
                    if (1 === t.pipesCount) return e && e !== t.pipes || (e || (e = t.pipes), t.pipes = null, t.pipesCount = 0, t.flowing = !1, e && e.emit("unpipe", this, r)), this;
                    if (!e) {
                        var n = t.pipes, i = t.pipesCount;
                        t.pipes = null, t.pipesCount = 0, t.flowing = !1;
                        for (var s = 0; s < i; s++) n[s].emit("unpipe", this, {hasUnpiped: !1});
                        return this
                    }
                    var o = F(t.pipes, e);
                    return -1 === o || (t.pipes.splice(o, 1), t.pipesCount -= 1, 1 === t.pipesCount && (t.pipes = t.pipes[0]), e.emit("unpipe", this, r)), this
                }, C.prototype.on = function (e, r) {
                    var n = oe.prototype.on.call(this, e, r), s = this._readableState;
                    return "data" === e ? (s.readableListening = this.listenerCount("readable") > 0, !1 !== s.flowing && this.resume()) : "readable" === e && (s.endEmitted || s.readableListening || (s.readableListening = s.needReadable = !0, s.flowing = !1, s.emittedReadable = !1, i("on readable", s.length, s.reading), s.length ? I(this) : s.reading || t.nextTick(U, this))), n
                }, C.prototype.addListener = C.prototype.on, C.prototype.removeListener = function (e, r) {
                    var n = oe.prototype.removeListener.call(this, e, r);
                    return "readable" === e && t.nextTick(O, this), n
                }, C.prototype.removeAllListeners = function (e) {
                    var r = oe.prototype.removeAllListeners.apply(this, arguments);
                    return "readable" !== e && void 0 !== e || t.nextTick(O, this), r
                }, C.prototype.resume = function () {
                    var e = this._readableState;
                    return e.flowing || (i("resume"), e.flowing = !e.readableListening, function (e, r) {
                        r.resumeScheduled || (r.resumeScheduled = !0, t.nextTick(P, e, r))
                    }(this, e)), e.paused = !1, this
                }, C.prototype.pause = function () {
                    return i("call pause flowing=%j", this._readableState.flowing), !1 !== this._readableState.flowing && (i("pause"), this._readableState.flowing = !1, this.emit("pause")), this._readableState.paused = !0, this
                }, C.prototype.wrap = function (e) {
                    var t = this, r = this._readableState, n = !1;
                    for (var s in e.on("end", (function () {
                        if (i("wrapped end"), r.decoder && !r.ended) {
                            var e = r.decoder.end();
                            e && e.length && t.push(e)
                        }
                        t.push(null)
                    })), e.on("data", (function (s) {
                        i("wrapped data"), r.decoder && (s = r.decoder.write(s)), r.objectMode && null == s || (r.objectMode || s && s.length) && (t.push(s) || (n = !0, e.pause()))
                    })), e) void 0 === this[s] && "function" == typeof e[s] && (this[s] = function (t) {
                        return function () {
                            return e[t].apply(e, arguments)
                        }
                    }(s));
                    for (var o = 0; o < k.length; o++) e.on(k[o], this.emit.bind(this, k[o]));
                    return this._read = function (t) {
                        i("wrapped _read", t), n && (n = !1, e.resume())
                    }, this
                }, "function" == typeof Symbol && (C.prototype[Symbol.asyncIterator] = function () {
                    return void 0 === u && (u = f({})), u(this)
                }), Object.defineProperty(C.prototype, "readableHighWaterMark", {
                    enumerable: !1, get: function () {
                        return this._readableState.highWaterMark
                    }
                }), Object.defineProperty(C.prototype, "readableBuffer", {
                    enumerable: !1, get: function () {
                        return this._readableState && this._readableState.buffer
                    }
                }), Object.defineProperty(C.prototype, "readableFlowing", {
                    enumerable: !1, get: function () {
                        return this._readableState.flowing
                    }, set: function (e) {
                        this._readableState && (this._readableState.flowing = e)
                    }
                }), C._fromList = D, Object.defineProperty(C.prototype, "readableLength", {
                    enumerable: !1,
                    get: function () {
                        return this._readableState.length
                    }
                }), "function" == typeof Symbol && (C.from = function (e, t) {
                    return void 0 === c && (c = l({})), c(C, e, t)
                })
            }).call(this)
        }).call(this, _e, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {})
    })), l = e((function (e, t) {
        e.exports = function () {
            throw new Error("Readable.from is not available in the browser")
        }
    })), f = e((function (e, t) {
        (function (t) {
            (function () {
                "use strict";
                var r;

                function n(e, t, r) {
                    return t in e ? Object.defineProperty(e, t, {
                        value: r,
                        enumerable: !0,
                        configurable: !0,
                        writable: !0
                    }) : e[t] = r, e
                }

                var i = p({}), s = Symbol("lastResolve"), o = Symbol("lastReject"), a = Symbol("error"),
                    h = Symbol("ended"), u = Symbol("lastPromise"), c = Symbol("handlePromise"), d = Symbol("stream");

                function l(e, t) {
                    return {value: e, done: t}
                }

                function f(e) {
                    var t = e[s];
                    if (null !== t) {
                        var r = e[d].read();
                        null !== r && (e[u] = null, e[s] = null, e[o] = null, t(l(r, !1)))
                    }
                }

                var m = Object.getPrototypeOf((function () {
                })), g = Object.setPrototypeOf((n(r = {
                    get stream() {
                        return this[d]
                    }, next: function () {
                        var e = this, r = this[a];
                        if (null !== r) return Promise.reject(r);
                        if (this[h]) return Promise.resolve(l(void 0, !0));
                        if (this[d].destroyed) return new Promise((function (r, n) {
                            t.nextTick((function () {
                                e[a] ? n(e[a]) : r(l(void 0, !0))
                            }))
                        }));
                        var n, i = this[u];
                        if (i) n = new Promise(function (e, t) {
                            return function (r, n) {
                                e.then((function () {
                                    t[h] ? r(l(void 0, !0)) : t[c](r, n)
                                }), n)
                            }
                        }(i, this)); else {
                            var s = this[d].read();
                            if (null !== s) return Promise.resolve(l(s, !1));
                            n = new Promise(this[c])
                        }
                        return this[u] = n, n
                    }
                }, Symbol.asyncIterator, (function () {
                    return this
                })), n(r, "return", (function () {
                    var e = this;
                    return new Promise((function (t, r) {
                        e[d].destroy(null, (function (e) {
                            e ? r(e) : t(l(void 0, !0))
                        }))
                    }))
                })), r), m);
                e.exports = function (e) {
                    var r, p = Object.create(g, (n(r = {}, d, {value: e, writable: !0}), n(r, s, {
                        value: null,
                        writable: !0
                    }), n(r, o, {value: null, writable: !0}), n(r, a, {
                        value: null,
                        writable: !0
                    }), n(r, h, {value: e._readableState.endEmitted, writable: !0}), n(r, c, {
                        value: function (e, t) {
                            var r = p[d].read();
                            r ? (p[u] = null, p[s] = null, p[o] = null, e(l(r, !1))) : (p[s] = e, p[o] = t)
                        }, writable: !0
                    }), r));
                    return p[u] = null, i(e, (function (e) {
                        if (e && "ERR_STREAM_PREMATURE_CLOSE" !== e.code) {
                            var t = p[o];
                            return null !== t && (p[u] = null, p[s] = null, p[o] = null, t(e)), void (p[a] = e)
                        }
                        var r = p[s];
                        null !== r && (p[u] = null, p[s] = null, p[o] = null, r(l(void 0, !0))), p[h] = !0
                    })), e.on("readable", function (e) {
                        t.nextTick(f, e)
                    }.bind(null, p)), p
                }
            }).call(this)
        }).call(this, _e)
    })), p = e((function (e, t) {
        "use strict";
        var r = Be.codes.ERR_STREAM_PREMATURE_CLOSE;

        function n() {
        }

        e.exports = function e(t, i, s) {
            if ("function" == typeof i) return e(t, null, i);
            i || (i = {}), s = function (e) {
                var t = !1;
                return function () {
                    if (!t) {
                        t = !0;
                        for (var r = arguments.length, n = new Array(r), i = 0; i < r; i++) n[i] = arguments[i];
                        e.apply(this, n)
                    }
                }
            }(s || n);
            var o = i.readable || !1 !== i.readable && t.readable, a = i.writable || !1 !== i.writable && t.writable,
                h = function () {
                    t.writable || c()
                }, u = t._writableState && t._writableState.finished, c = function () {
                    a = !1, u = !0, o || s.call(t)
                }, d = t._readableState && t._readableState.endEmitted, l = function () {
                    o = !1, d = !0, a || s.call(t)
                }, f = function (e) {
                    s.call(t, e)
                }, p = function () {
                    var e;
                    return o && !d ? (t._readableState && t._readableState.ended || (e = new r), s.call(t, e)) : a && !u ? (t._writableState && t._writableState.ended || (e = new r), s.call(t, e)) : void 0
                }, m = function () {
                    t.req.on("finish", c)
                };
            return function (e) {
                return e.setHeader && "function" == typeof e.abort
            }(t) ? (t.on("complete", c), t.on("abort", p), t.req ? m() : t.on("request", m)) : a && !t._writableState && (t.on("end", h), t.on("close", h)), t.on("end", l), t.on("finish", c), !1 !== i.error && t.on("error", f), t.on("close", p), function () {
                t.removeListener("complete", c), t.removeListener("abort", p), t.removeListener("request", m), t.req && t.req.removeListener("finish", c), t.removeListener("end", h), t.removeListener("close", h), t.removeListener("finish", c), t.removeListener("end", l), t.removeListener("error", f), t.removeListener("close", p)
            }
        }
    })), m = e((function (e, t) {
        "use strict";
        var r = je.Buffer, n = r.isEncoding || function (e) {
            switch ((e = "" + e) && e.toLowerCase()) {
                case"hex":
                case"utf8":
                case"utf-8":
                case"ascii":
                case"binary":
                case"base64":
                case"ucs2":
                case"ucs-2":
                case"utf16le":
                case"utf-16le":
                case"raw":
                    return !0;
                default:
                    return !1
            }
        };

        function i(e) {
            var t;
            switch (this.encoding = function (e) {
                var t = function (e) {
                    if (!e) return "utf8";
                    for (var t; ;) switch (e) {
                        case"utf8":
                        case"utf-8":
                            return "utf8";
                        case"ucs2":
                        case"ucs-2":
                        case"utf16le":
                        case"utf-16le":
                            return "utf16le";
                        case"latin1":
                        case"binary":
                            return "latin1";
                        case"base64":
                        case"ascii":
                        case"hex":
                            return e;
                        default:
                            if (t) return;
                            e = ("" + e).toLowerCase(), t = !0
                    }
                }(e);
                if ("string" != typeof t && (r.isEncoding === n || !n(e))) throw new Error("Unknown encoding: " + e);
                return t || e
            }(e), this.encoding) {
                case"utf16le":
                    this.text = a, this.end = h, t = 4;
                    break;
                case"utf8":
                    this.fillLast = o, t = 4;
                    break;
                case"base64":
                    this.text = u, this.end = c, t = 3;
                    break;
                default:
                    return this.write = d, void (this.end = l)
            }
            this.lastNeed = 0, this.lastTotal = 0, this.lastChar = r.allocUnsafe(t)
        }

        function s(e) {
            return e <= 127 ? 0 : e >> 5 == 6 ? 2 : e >> 4 == 14 ? 3 : e >> 3 == 30 ? 4 : e >> 6 == 2 ? -1 : -2
        }

        function o(e) {
            var t = this.lastTotal - this.lastNeed, r = function (e, t, r) {
                if (128 != (192 & t[0])) return e.lastNeed = 0, "\ufffd";
                if (e.lastNeed > 1 && t.length > 1) {
                    if (128 != (192 & t[1])) return e.lastNeed = 1, "\ufffd";
                    if (e.lastNeed > 2 && t.length > 2 && 128 != (192 & t[2])) return e.lastNeed = 2, "\ufffd"
                }
            }(this, e);
            return void 0 !== r ? r : this.lastNeed <= e.length ? (e.copy(this.lastChar, t, 0, this.lastNeed), this.lastChar.toString(this.encoding, 0, this.lastTotal)) : (e.copy(this.lastChar, t, 0, e.length), void (this.lastNeed -= e.length))
        }

        function a(e, t) {
            if ((e.length - t) % 2 == 0) {
                var r = e.toString("utf16le", t);
                if (r) {
                    var n = r.charCodeAt(r.length - 1);
                    if (n >= 55296 && n <= 56319) return this.lastNeed = 2, this.lastTotal = 4, this.lastChar[0] = e[e.length - 2], this.lastChar[1] = e[e.length - 1], r.slice(0, -1)
                }
                return r
            }
            return this.lastNeed = 1, this.lastTotal = 2, this.lastChar[0] = e[e.length - 1], e.toString("utf16le", t, e.length - 1)
        }

        function h(e) {
            var t = e && e.length ? this.write(e) : "";
            if (this.lastNeed) {
                var r = this.lastTotal - this.lastNeed;
                return t + this.lastChar.toString("utf16le", 0, r)
            }
            return t
        }

        function u(e, t) {
            var r = (e.length - t) % 3;
            return 0 === r ? e.toString("base64", t) : (this.lastNeed = 3 - r, this.lastTotal = 3, 1 === r ? this.lastChar[0] = e[e.length - 1] : (this.lastChar[0] = e[e.length - 2], this.lastChar[1] = e[e.length - 1]), e.toString("base64", t, e.length - r))
        }

        function c(e) {
            var t = e && e.length ? this.write(e) : "";
            return this.lastNeed ? t + this.lastChar.toString("base64", 0, 3 - this.lastNeed) : t
        }

        function d(e) {
            return e.toString(this.encoding)
        }

        function l(e) {
            return e && e.length ? this.write(e) : ""
        }

        t.StringDecoder = i, i.prototype.write = function (e) {
            if (0 === e.length) return "";
            var t, r;
            if (this.lastNeed) {
                if (void 0 === (t = this.fillLast(e))) return "";
                r = this.lastNeed, this.lastNeed = 0
            } else r = 0;
            return r < e.length ? t ? t + this.text(e, r) : this.text(e, r) : t || ""
        }, i.prototype.end = function (e) {
            var t = e && e.length ? this.write(e) : "";
            return this.lastNeed ? t + "\ufffd" : t
        }, i.prototype.text = function (e, t) {
            var r = function (e, t, r) {
                var n = t.length - 1;
                if (n < r) return 0;
                var i = s(t[n]);
                return i >= 0 ? (i > 0 && (e.lastNeed = i - 1), i) : --n < r || -2 === i ? 0 : (i = s(t[n])) >= 0 ? (i > 0 && (e.lastNeed = i - 2), i) : --n < r || -2 === i ? 0 : (i = s(t[n])) >= 0 ? (i > 0 && (2 === i ? i = 0 : e.lastNeed = i - 3), i) : 0
            }(this, e, t);
            if (!this.lastNeed) return e.toString("utf8", t);
            this.lastTotal = r;
            var n = e.length - (r - this.lastNeed);
            return e.copy(this.lastChar, 0, n), e.toString("utf8", t, n)
        }, i.prototype.fillLast = function (e) {
            if (this.lastNeed <= e.length) return e.copy(this.lastChar, this.lastTotal - this.lastNeed, 0, this.lastNeed), this.lastChar.toString(this.encoding, 0, this.lastTotal);
            e.copy(this.lastChar, this.lastTotal - this.lastNeed, 0, e.length), this.lastNeed -= e.length
        }
    })), g = e((function (e, t) {
        (function (t) {
            (function () {
                "use strict";
                var r = Object.keys || function (e) {
                    var t = [];
                    for (var r in e) t.push(r);
                    return t
                };
                e.exports = h;
                var n = d({}), i = _({});
                De(h, n);
                for (var s = r(i.prototype), o = 0; o < s.length; o++) {
                    var a = s[o];
                    h.prototype[a] || (h.prototype[a] = i.prototype[a])
                }

                function h(e) {
                    if (!(this instanceof h)) return new h(e);
                    n.call(this, e), i.call(this, e), this.allowHalfOpen = !0, e && (!1 === e.readable && (this.readable = !1), !1 === e.writable && (this.writable = !1), !1 === e.allowHalfOpen && (this.allowHalfOpen = !1, this.once("end", u)))
                }

                function u() {
                    this._writableState.ended || t.nextTick(c, this)
                }

                function c(e) {
                    e.end()
                }

                Object.defineProperty(h.prototype, "writableHighWaterMark", {
                    enumerable: !1, get: function () {
                        return this._writableState.highWaterMark
                    }
                }), Object.defineProperty(h.prototype, "writableBuffer", {
                    enumerable: !1, get: function () {
                        return this._writableState && this._writableState.getBuffer()
                    }
                }), Object.defineProperty(h.prototype, "writableLength", {
                    enumerable: !1, get: function () {
                        return this._writableState.length
                    }
                }), Object.defineProperty(h.prototype, "destroyed", {
                    enumerable: !1, get: function () {
                        return void 0 !== this._readableState && void 0 !== this._writableState && this._readableState.destroyed && this._writableState.destroyed
                    }, set: function (e) {
                        void 0 !== this._readableState && void 0 !== this._writableState && (this._readableState.destroyed = e, this._writableState.destroyed = e)
                    }
                })
            }).call(this)
        }).call(this, _e)
    })), _ = e((function (e, t) {
        (function (t, r) {
            (function () {
                "use strict";

                function n(e) {
                    var t = this;
                    this.next = null, this.entry = null, this.finish = function () {
                        !function (e, t, r) {
                            var n = e.entry;
                            for (e.entry = null; n;) {
                                var i = n.callback;
                                t.pendingcb--, i(void 0), n = n.next
                            }
                            t.corkedRequestsFree.next = e
                        }(t, e)
                    }
                }

                var i;
                e.exports = S, S.WritableState = k;
                var s, o = {deprecate: Ne}, a = y({}).Buffer, h = r.Uint8Array || function () {
                    }, u = Me.getHighWaterMark, c = Be.codes, d = c.ERR_INVALID_ARG_TYPE, l = c.ERR_METHOD_NOT_IMPLEMENTED,
                    f = c.ERR_MULTIPLE_CALLBACK, p = c.ERR_STREAM_CANNOT_PIPE, m = c.ERR_STREAM_DESTROYED,
                    _ = c.ERR_STREAM_NULL_VALUES, b = c.ERR_STREAM_WRITE_AFTER_END, v = c.ERR_UNKNOWN_ENCODING,
                    w = Re.errorOrDestroy;

                function E() {
                }

                function k(e, r, s) {
                    i = i || g({}), e = e || {}, "boolean" != typeof s && (s = r instanceof i), this.objectMode = !!e.objectMode, s && (this.objectMode = this.objectMode || !!e.writableObjectMode), this.highWaterMark = u(this, e, "writableHighWaterMark", s), this.finalCalled = !1, this.needDrain = !1, this.ending = !1, this.ended = !1, this.finished = !1, this.destroyed = !1;
                    var o = !1 === e.decodeStrings;
                    this.decodeStrings = !o, this.defaultEncoding = e.defaultEncoding || "utf8", this.length = 0, this.writing = !1, this.corked = 0, this.sync = !0, this.bufferProcessing = !1, this.onwrite = function (e) {
                        !function (e, r) {
                            var n = e._writableState, i = n.sync, s = n.writecb;
                            if ("function" != typeof s) throw new f;
                            if (function (e) {
                                e.writing = !1, e.writecb = null, e.length -= e.writelen, e.writelen = 0
                            }(n), r) !function (e, r, n, i, s) {
                                --r.pendingcb, n ? (t.nextTick(s, i), t.nextTick(R, e, r), e._writableState.errorEmitted = !0, w(e, i)) : (s(i), e._writableState.errorEmitted = !0, w(e, i), R(e, r))
                            }(e, n, i, r, s); else {
                                var o = T(n) || e.destroyed;
                                o || n.corked || n.bufferProcessing || !n.bufferedRequest || A(e, n), i ? t.nextTick(x, e, n, o, s) : x(e, n, o, s)
                            }
                        }(r, e)
                    }, this.writecb = null, this.writelen = 0, this.bufferedRequest = null, this.lastBufferedRequest = null, this.pendingcb = 0, this.prefinished = !1, this.errorEmitted = !1, this.emitClose = !1 !== e.emitClose, this.autoDestroy = !!e.autoDestroy, this.bufferedRequestCount = 0, this.corkedRequestsFree = new n(this)
                }

                function S(e) {
                    var t = this instanceof (i = i || g({}));
                    if (!t && !s.call(S, this)) return new S(e);
                    this._writableState = new k(e, this, t), this.writable = !0, e && ("function" == typeof e.write && (this._write = e.write), "function" == typeof e.writev && (this._writev = e.writev), "function" == typeof e.destroy && (this._destroy = e.destroy), "function" == typeof e.final && (this._final = e.final)), oe.call(this)
                }

                function C(e, t, r, n, i, s, o) {
                    t.writelen = n, t.writecb = o, t.writing = !0, t.sync = !0, t.destroyed ? t.onwrite(new m("write")) : r ? e._writev(i, t.onwrite) : e._write(i, s, t.onwrite), t.sync = !1
                }

                function x(e, t, r, n) {
                    r || function (e, t) {
                        0 === t.length && t.needDrain && (t.needDrain = !1, e.emit("drain"))
                    }(e, t), t.pendingcb--, n(), R(e, t)
                }

                function A(e, t) {
                    t.bufferProcessing = !0;
                    var r = t.bufferedRequest;
                    if (e._writev && r && r.next) {
                        var i = t.bufferedRequestCount, s = new Array(i), o = t.corkedRequestsFree;
                        o.entry = r;
                        for (var a = 0, h = !0; r;) s[a] = r, r.isBuf || (h = !1), r = r.next, a += 1;
                        s.allBuffers = h, C(e, t, !0, t.length, s, "", o.finish), t.pendingcb++, t.lastBufferedRequest = null, o.next ? (t.corkedRequestsFree = o.next, o.next = null) : t.corkedRequestsFree = new n(t), t.bufferedRequestCount = 0
                    } else {
                        for (; r;) {
                            var u = r.chunk, c = r.encoding, d = r.callback;
                            if (C(e, t, !1, t.objectMode ? 1 : u.length, u, c, d), r = r.next, t.bufferedRequestCount--, t.writing) break
                        }
                        null === r && (t.lastBufferedRequest = null)
                    }
                    t.bufferedRequest = r, t.bufferProcessing = !1
                }

                function T(e) {
                    return e.ending && 0 === e.length && null === e.bufferedRequest && !e.finished && !e.writing
                }

                function I(e, t) {
                    e._final((function (r) {
                        t.pendingcb--, r && w(e, r), t.prefinished = !0, e.emit("prefinish"), R(e, t)
                    }))
                }

                function R(e, r) {
                    var n = T(r);
                    if (n && (function (e, r) {
                        r.prefinished || r.finalCalled || ("function" != typeof e._final || r.destroyed ? (r.prefinished = !0, e.emit("prefinish")) : (r.pendingcb++, r.finalCalled = !0, t.nextTick(I, e, r)))
                    }(e, r), 0 === r.pendingcb && (r.finished = !0, e.emit("finish"), r.autoDestroy))) {
                        var i = e._readableState;
                        (!i || i.autoDestroy && i.endEmitted) && e.destroy()
                    }
                    return n
                }

                De(S, oe), k.prototype.getBuffer = function () {
                    for (var e = this.bufferedRequest, t = []; e;) t.push(e), e = e.next;
                    return t
                }, function () {
                    try {
                        Object.defineProperty(k.prototype, "buffer", {
                            get: o.deprecate((function () {
                                return this.getBuffer()
                            }), "_writableState.buffer is deprecated. Use _writableState.getBuffer instead.", "DEP0003")
                        })
                    } catch (e) {
                    }
                }(), "function" == typeof Symbol && Symbol.hasInstance && "function" == typeof Function.prototype[Symbol.hasInstance] ? (s = Function.prototype[Symbol.hasInstance], Object.defineProperty(S, Symbol.hasInstance, {
                    value: function (e) {
                        return !!s.call(this, e) || this === S && e && e._writableState instanceof k
                    }
                })) : s = function (e) {
                    return e instanceof this
                }, S.prototype.pipe = function () {
                    w(this, new p)
                }, S.prototype.write = function (e, r, n) {
                    var i, s = this._writableState, o = !1,
                        u = !s.objectMode && (i = e, a.isBuffer(i) || i instanceof h);
                    return u && !a.isBuffer(e) && (e = function (e) {
                        return a.from(e)
                    }(e)), "function" == typeof r && (n = r, r = null), u ? r = "buffer" : r || (r = s.defaultEncoding), "function" != typeof n && (n = E), s.ending ? function (e, r) {
                        var n = new b;
                        w(e, n), t.nextTick(r, n)
                    }(this, n) : (u || function (e, r, n, i) {
                        var s;
                        return null === n ? s = new _ : "string" == typeof n || r.objectMode || (s = new d("chunk", ["string", "Buffer"], n)), !s || (w(e, s), t.nextTick(i, s), !1)
                    }(this, s, e, n)) && (s.pendingcb++, o = function (e, t, r, n, i, s) {
                        if (!r) {
                            var o = function (e, t, r) {
                                return e.objectMode || !1 === e.decodeStrings || "string" != typeof t || (t = a.from(t, r)), t
                            }(t, n, i);
                            n !== o && (r = !0, i = "buffer", n = o)
                        }
                        var h = t.objectMode ? 1 : n.length;
                        t.length += h;
                        var u = t.length < t.highWaterMark;
                        if (u || (t.needDrain = !0), t.writing || t.corked) {
                            var c = t.lastBufferedRequest;
                            t.lastBufferedRequest = {
                                chunk: n,
                                encoding: i,
                                isBuf: r,
                                callback: s,
                                next: null
                            }, c ? c.next = t.lastBufferedRequest : t.bufferedRequest = t.lastBufferedRequest, t.bufferedRequestCount += 1
                        } else C(e, t, !1, h, n, i, s);
                        return u
                    }(this, s, u, e, r, n)), o
                }, S.prototype.cork = function () {
                    this._writableState.corked++
                }, S.prototype.uncork = function () {
                    var e = this._writableState;
                    e.corked && (e.corked--, e.writing || e.corked || e.bufferProcessing || !e.bufferedRequest || A(this, e))
                }, S.prototype.setDefaultEncoding = function (e) {
                    if ("string" == typeof e && (e = e.toLowerCase()), !(["hex", "utf8", "utf-8", "ascii", "binary", "base64", "ucs2", "ucs-2", "utf16le", "utf-16le", "raw"].indexOf((e + "").toLowerCase()) > -1)) throw new v(e);
                    return this._writableState.defaultEncoding = e, this
                }, Object.defineProperty(S.prototype, "writableBuffer", {
                    enumerable: !1, get: function () {
                        return this._writableState && this._writableState.getBuffer()
                    }
                }), Object.defineProperty(S.prototype, "writableHighWaterMark", {
                    enumerable: !1, get: function () {
                        return this._writableState.highWaterMark
                    }
                }), S.prototype._write = function (e, t, r) {
                    r(new l("_write()"))
                }, S.prototype._writev = null, S.prototype.end = function (e, r, n) {
                    var i = this._writableState;
                    return "function" == typeof e ? (n = e, e = null, r = null) : "function" == typeof r && (n = r, r = null), null != e && this.write(e, r), i.corked && (i.corked = 1, this.uncork()), i.ending || function (e, r, n) {
                        r.ending = !0, R(e, r), n && (r.finished ? t.nextTick(n) : e.once("finish", n)), r.ended = !0, e.writable = !1
                    }(this, i, n), this
                }, Object.defineProperty(S.prototype, "writableLength", {
                    enumerable: !1, get: function () {
                        return this._writableState.length
                    }
                }), Object.defineProperty(S.prototype, "destroyed", {
                    enumerable: !1, get: function () {
                        return void 0 !== this._writableState && this._writableState.destroyed
                    }, set: function (e) {
                        this._writableState && (this._writableState.destroyed = e)
                    }
                }), S.prototype.destroy = Re.destroy, S.prototype._undestroy = Re.undestroy, S.prototype._destroy = function (e, t) {
                    t(e)
                }
            }).call(this)
        }).call(this, _e, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {})
    })), y = e((function (e, t) {
        (function (e) {
            (function () {
                "use strict";
                t.Buffer = r, t.SlowBuffer = function (e) {
                    return +e != e && (e = 0), r.alloc(+e)
                }, t.INSPECT_MAX_BYTES = 50;

                function e(e) {
                    if (e > 2147483647) throw new RangeError('The value "' + e + '" is invalid for option "size"');
                    var t = new Uint8Array(e);
                    return t.__proto__ = r.prototype, t
                }

                function r(e, t, r) {
                    if ("number" == typeof e) {
                        if ("string" == typeof t) throw new TypeError('The "string" argument must be of type string. Received type number');
                        return s(e)
                    }
                    return n(e, t, r)
                }

                function n(t, n, i) {
                    if ("string" == typeof t) return function (t, n) {
                        if ("string" == typeof n && "" !== n || (n = "utf8"), !r.isEncoding(n)) throw new TypeError("Unknown encoding: " + n);
                        var i = 0 | h(t, n), s = e(i), o = s.write(t, n);
                        return o !== i && (s = s.slice(0, o)), s
                    }(t, n);
                    if (ArrayBuffer.isView(t)) return o(t);
                    if (null == t) throw TypeError("The first argument must be one of type string, Buffer, ArrayBuffer, Array, or Array-like Object. Received type " + typeof t);
                    if (M(t, ArrayBuffer) || t && M(t.buffer, ArrayBuffer)) return function (e, t, n) {
                        if (t < 0 || e.byteLength < t) throw new RangeError('"offset" is outside of buffer bounds');
                        if (e.byteLength < t + (n || 0)) throw new RangeError('"length" is outside of buffer bounds');
                        var i;
                        return (i = void 0 === t && void 0 === n ? new Uint8Array(e) : void 0 === n ? new Uint8Array(e, t) : new Uint8Array(e, t, n)).__proto__ = r.prototype, i
                    }(t, n, i);
                    if ("number" == typeof t) throw new TypeError('The "value" argument must not be of type number. Received type number');
                    var s = t.valueOf && t.valueOf();
                    if (null != s && s !== t) return r.from(s, n, i);
                    var u = function (t) {
                        if (r.isBuffer(t)) {
                            var n = 0 | a(t.length), i = e(n);
                            return 0 === i.length || t.copy(i, 0, 0, n), i
                        }
                        return void 0 !== t.length ? "number" != typeof t.length || D(t.length) ? e(0) : o(t) : "Buffer" === t.type && Array.isArray(t.data) ? o(t.data) : void 0
                    }(t);
                    if (u) return u;
                    if ("undefined" != typeof Symbol && null != Symbol.toPrimitive && "function" == typeof t[Symbol.toPrimitive]) return r.from(t[Symbol.toPrimitive]("string"), n, i);
                    throw new TypeError("The first argument must be one of type string, Buffer, ArrayBuffer, Array, or Array-like Object. Received type " + typeof t)
                }

                function i(e) {
                    if ("number" != typeof e) throw new TypeError('"size" argument must be of type number');
                    if (e < 0) throw new RangeError('The value "' + e + '" is invalid for option "size"')
                }

                function s(t) {
                    return i(t), e(t < 0 ? 0 : 0 | a(t))
                }

                function o(t) {
                    for (var r = t.length < 0 ? 0 : 0 | a(t.length), n = e(r), i = 0; i < r; i += 1) n[i] = 255 & t[i];
                    return n
                }

                function a(e) {
                    if (e >= 2147483647) throw new RangeError("Attempt to allocate Buffer larger than maximum size: 0x" + 2147483647..toString(16) + " bytes");
                    return 0 | e
                }

                function h(e, t) {
                    if (r.isBuffer(e)) return e.length;
                    if (ArrayBuffer.isView(e) || M(e, ArrayBuffer)) return e.byteLength;
                    if ("string" != typeof e) throw new TypeError('The "string" argument must be one of type string, Buffer, or ArrayBuffer. Received type ' + typeof e);
                    var n = e.length, i = arguments.length > 2 && !0 === arguments[2];
                    if (!i && 0 === n) return 0;
                    for (var s = !1; ;) switch (t) {
                        case"ascii":
                        case"latin1":
                        case"binary":
                            return n;
                        case"utf8":
                        case"utf-8":
                            return O(e).length;
                        case"ucs2":
                        case"ucs-2":
                        case"utf16le":
                        case"utf-16le":
                            return 2 * n;
                        case"hex":
                            return n >>> 1;
                        case"base64":
                            return U(e).length;
                        default:
                            if (s) return i ? -1 : O(e).length;
                            t = ("" + t).toLowerCase(), s = !0
                    }
                }

                function u(e, t, r) {
                    var n = e[t];
                    e[t] = e[r], e[r] = n
                }

                function c(e, t, n, i, s) {
                    if (0 === e.length) return -1;
                    if ("string" == typeof n ? (i = n, n = 0) : n > 2147483647 ? n = 2147483647 : n < -2147483648 && (n = -2147483648), D(n = +n) && (n = s ? 0 : e.length - 1), n < 0 && (n = e.length + n), n >= e.length) {
                        if (s) return -1;
                        n = e.length - 1
                    } else if (n < 0) {
                        if (!s) return -1;
                        n = 0
                    }
                    if ("string" == typeof t && (t = r.from(t, i)), r.isBuffer(t)) return 0 === t.length ? -1 : d(e, t, n, i, s);
                    if ("number" == typeof t) return t &= 255, "function" == typeof Uint8Array.prototype.indexOf ? s ? Uint8Array.prototype.indexOf.call(e, t, n) : Uint8Array.prototype.lastIndexOf.call(e, t, n) : d(e, [t], n, i, s);
                    throw new TypeError("val must be string, number or Buffer")
                }

                function d(e, t, r, n, i) {
                    var s, o = 1, a = e.length, h = t.length;
                    if (void 0 !== n && ("ucs2" === (n = String(n).toLowerCase()) || "ucs-2" === n || "utf16le" === n || "utf-16le" === n)) {
                        if (e.length < 2 || t.length < 2) return -1;
                        o = 2, a /= 2, h /= 2, r /= 2
                    }

                    function u(e, t) {
                        return 1 === o ? e[t] : e.readUInt16BE(t * o)
                    }

                    if (i) {
                        var c = -1;
                        for (s = r; s < a; s++) if (u(e, s) === u(t, -1 === c ? 0 : s - c)) {
                            if (-1 === c && (c = s), s - c + 1 === h) return c * o
                        } else -1 !== c && (s -= s - c), c = -1
                    } else for (r + h > a && (r = a - h), s = r; s >= 0; s--) {
                        for (var d = !0, l = 0; l < h; l++) if (u(e, s + l) !== u(t, l)) {
                            d = !1;
                            break
                        }
                        if (d) return s
                    }
                    return -1
                }

                function l(e, t, r, n) {
                    r = Number(r) || 0;
                    var i = e.length - r;
                    n ? (n = Number(n)) > i && (n = i) : n = i;
                    var s = t.length;
                    n > s / 2 && (n = s / 2);
                    for (var o = 0; o < n; ++o) {
                        var a = parseInt(t.substr(2 * o, 2), 16);
                        if (D(a)) return o;
                        e[r + o] = a
                    }
                    return o
                }

                function f(e, t, r, n) {
                    return P(O(t, e.length - r), e, r, n)
                }

                function p(e, t, r, n) {
                    return P(function (e) {
                        for (var t = [], r = 0; r < e.length; ++r) t.push(255 & e.charCodeAt(r));
                        return t
                    }(t), e, r, n)
                }

                function m(e, t, r, n) {
                    return p(e, t, r, n)
                }

                function g(e, t, r, n) {
                    return P(U(t), e, r, n)
                }

                function _(e, t, r, n) {
                    return P(function (e, t) {
                        for (var r, n, i, s = [], o = 0; o < e.length && !((t -= 2) < 0); ++o) n = (r = e.charCodeAt(o)) >> 8, i = r % 256, s.push(i), s.push(n);
                        return s
                    }(t, e.length - r), e, r, n)
                }

                function y(e, t, r) {
                    return 0 === t && r === e.length ? b.fromByteArray(e) : b.fromByteArray(e.slice(t, r))
                }

                function v(e, t, r) {
                    r = Math.min(e.length, r);
                    for (var n = [], i = t; i < r;) {
                        var s, o, a, h, u = e[i], c = null, d = u > 239 ? 4 : u > 223 ? 3 : u > 191 ? 2 : 1;
                        if (i + d <= r) switch (d) {
                            case 1:
                                u < 128 && (c = u);
                                break;
                            case 2:
                                128 == (192 & (s = e[i + 1])) && (h = (31 & u) << 6 | 63 & s) > 127 && (c = h);
                                break;
                            case 3:
                                s = e[i + 1], o = e[i + 2], 128 == (192 & s) && 128 == (192 & o) && (h = (15 & u) << 12 | (63 & s) << 6 | 63 & o) > 2047 && (h < 55296 || h > 57343) && (c = h);
                                break;
                            case 4:
                                s = e[i + 1], o = e[i + 2], a = e[i + 3], 128 == (192 & s) && 128 == (192 & o) && 128 == (192 & a) && (h = (15 & u) << 18 | (63 & s) << 12 | (63 & o) << 6 | 63 & a) > 65535 && h < 1114112 && (c = h)
                        }
                        null === c ? (c = 65533, d = 1) : c > 65535 && (c -= 65536, n.push(c >>> 10 & 1023 | 55296), c = 56320 | 1023 & c), n.push(c), i += d
                    }
                    return function (e) {
                        var t = e.length;
                        if (t <= w) return String.fromCharCode.apply(String, e);
                        for (var r = "", n = 0; n < t;) r += String.fromCharCode.apply(String, e.slice(n, n += w));
                        return r
                    }(n)
                }

                t.kMaxLength = 2147483647, r.TYPED_ARRAY_SUPPORT = function () {
                    try {
                        var e = new Uint8Array(1);
                        return e.__proto__ = {
                            __proto__: Uint8Array.prototype, foo: function () {
                                return 42
                            }
                        }, 42 === e.foo()
                    } catch (t) {
                        return !1
                    }
                }(), r.TYPED_ARRAY_SUPPORT || "undefined" == typeof console || "function" != typeof console.error || console.error("This browser lacks typed array (Uint8Array) support which is required by `buffer` v5.x. Use `buffer` v4.x if you require old browser support."), Object.defineProperty(r.prototype, "parent", {
                    enumerable: !0,
                    get: function () {
                        if (r.isBuffer(this)) return this.buffer
                    }
                }), Object.defineProperty(r.prototype, "offset", {
                    enumerable: !0, get: function () {
                        if (r.isBuffer(this)) return this.byteOffset
                    }
                }), "undefined" != typeof Symbol && null != Symbol.species && r[Symbol.species] === r && Object.defineProperty(r, Symbol.species, {
                    value: null,
                    configurable: !0,
                    enumerable: !1,
                    writable: !1
                }), r.poolSize = 8192, r.from = function (e, t, r) {
                    return n(e, t, r)
                }, r.prototype.__proto__ = Uint8Array.prototype, r.__proto__ = Uint8Array, r.alloc = function (t, r, n) {
                    return function (t, r, n) {
                        return i(t), t <= 0 ? e(t) : void 0 !== r ? "string" == typeof n ? e(t).fill(r, n) : e(t).fill(r) : e(t)
                    }(t, r, n)
                }, r.allocUnsafe = function (e) {
                    return s(e)
                }, r.allocUnsafeSlow = function (e) {
                    return s(e)
                }, r.isBuffer = function (e) {
                    return null != e && !0 === e._isBuffer && e !== r.prototype
                }, r.compare = function (e, t) {
                    if (M(e, Uint8Array) && (e = r.from(e, e.offset, e.byteLength)), M(t, Uint8Array) && (t = r.from(t, t.offset, t.byteLength)), !r.isBuffer(e) || !r.isBuffer(t)) throw new TypeError('The "buf1", "buf2" arguments must be one of type Buffer or Uint8Array');
                    if (e === t) return 0;
                    for (var n = e.length, i = t.length, s = 0, o = Math.min(n, i); s < o; ++s) if (e[s] !== t[s]) {
                        n = e[s], i = t[s];
                        break
                    }
                    return n < i ? -1 : i < n ? 1 : 0
                }, r.isEncoding = function (e) {
                    switch (String(e).toLowerCase()) {
                        case"hex":
                        case"utf8":
                        case"utf-8":
                        case"ascii":
                        case"latin1":
                        case"binary":
                        case"base64":
                        case"ucs2":
                        case"ucs-2":
                        case"utf16le":
                        case"utf-16le":
                            return !0;
                        default:
                            return !1
                    }
                }, r.concat = function (e, t) {
                    if (!Array.isArray(e)) throw new TypeError('"list" argument must be an Array of Buffers');
                    if (0 === e.length) return r.alloc(0);
                    var n;
                    if (void 0 === t) for (t = 0, n = 0; n < e.length; ++n) t += e[n].length;
                    var i = r.allocUnsafe(t), s = 0;
                    for (n = 0; n < e.length; ++n) {
                        var o = e[n];
                        if (M(o, Uint8Array) && (o = r.from(o)), !r.isBuffer(o)) throw new TypeError('"list" argument must be an Array of Buffers');
                        o.copy(i, s), s += o.length
                    }
                    return i
                }, r.byteLength = h, r.prototype._isBuffer = !0, r.prototype.swap16 = function () {
                    var e = this.length;
                    if (e % 2 != 0) throw new RangeError("Buffer size must be a multiple of 16-bits");
                    for (var t = 0; t < e; t += 2) u(this, t, t + 1);
                    return this
                }, r.prototype.swap32 = function () {
                    var e = this.length;
                    if (e % 4 != 0) throw new RangeError("Buffer size must be a multiple of 32-bits");
                    for (var t = 0; t < e; t += 4) u(this, t, t + 3), u(this, t + 1, t + 2);
                    return this
                }, r.prototype.swap64 = function () {
                    var e = this.length;
                    if (e % 8 != 0) throw new RangeError("Buffer size must be a multiple of 64-bits");
                    for (var t = 0; t < e; t += 8) u(this, t, t + 7), u(this, t + 1, t + 6), u(this, t + 2, t + 5), u(this, t + 3, t + 4);
                    return this
                }, r.prototype.toString = function () {
                    var e = this.length;
                    return 0 === e ? "" : 0 === arguments.length ? v(this, 0, e) : function (e, t, r) {
                        var n = !1;
                        if ((void 0 === t || t < 0) && (t = 0), t > this.length) return "";
                        if ((void 0 === r || r > this.length) && (r = this.length), r <= 0) return "";
                        if ((r >>>= 0) <= (t >>>= 0)) return "";
                        for (e || (e = "utf8"); ;) switch (e) {
                            case"hex":
                                return S(this, t, r);
                            case"utf8":
                            case"utf-8":
                                return v(this, t, r);
                            case"ascii":
                                return E(this, t, r);
                            case"latin1":
                            case"binary":
                                return k(this, t, r);
                            case"base64":
                                return y(this, t, r);
                            case"ucs2":
                            case"ucs-2":
                            case"utf16le":
                            case"utf-16le":
                                return C(this, t, r);
                            default:
                                if (n) throw new TypeError("Unknown encoding: " + e);
                                e = (e + "").toLowerCase(), n = !0
                        }
                    }.apply(this, arguments)
                }, r.prototype.toLocaleString = r.prototype.toString, r.prototype.equals = function (e) {
                    if (!r.isBuffer(e)) throw new TypeError("Argument must be a Buffer");
                    return this === e || 0 === r.compare(this, e)
                }, r.prototype.inspect = function () {
                    var e = "", r = t.INSPECT_MAX_BYTES;
                    return e = this.toString("hex", 0, r).replace(/(.{2})/g, "$1 ").trim(), this.length > r && (e += " ... "), "<Buffer " + e + ">"
                }, r.prototype.compare = function (e, t, n, i, s) {
                    if (M(e, Uint8Array) && (e = r.from(e, e.offset, e.byteLength)), !r.isBuffer(e)) throw new TypeError('The "target" argument must be one of type Buffer or Uint8Array. Received type ' + typeof e);
                    if (void 0 === t && (t = 0), void 0 === n && (n = e ? e.length : 0), void 0 === i && (i = 0), void 0 === s && (s = this.length), t < 0 || n > e.length || i < 0 || s > this.length) throw new RangeError("out of range index");
                    if (i >= s && t >= n) return 0;
                    if (i >= s) return -1;
                    if (t >= n) return 1;
                    if (this === e) return 0;
                    for (var o = (s >>>= 0) - (i >>>= 0), a = (n >>>= 0) - (t >>>= 0), h = Math.min(o, a), u = this.slice(i, s), c = e.slice(t, n), d = 0; d < h; ++d) if (u[d] !== c[d]) {
                        o = u[d], a = c[d];
                        break
                    }
                    return o < a ? -1 : a < o ? 1 : 0
                }, r.prototype.includes = function (e, t, r) {
                    return -1 !== this.indexOf(e, t, r)
                }, r.prototype.indexOf = function (e, t, r) {
                    return c(this, e, t, r, !0)
                }, r.prototype.lastIndexOf = function (e, t, r) {
                    return c(this, e, t, r, !1)
                }, r.prototype.write = function (e, t, r, n) {
                    if (void 0 === t) n = "utf8", r = this.length, t = 0; else if (void 0 === r && "string" == typeof t) n = t, r = this.length, t = 0; else {
                        if (!isFinite(t)) throw new Error("Buffer.write(string, encoding, offset[, length]) is no longer supported");
                        t >>>= 0, isFinite(r) ? (r >>>= 0, void 0 === n && (n = "utf8")) : (n = r, r = void 0)
                    }
                    var i = this.length - t;
                    if ((void 0 === r || r > i) && (r = i), e.length > 0 && (r < 0 || t < 0) || t > this.length) throw new RangeError("Attempt to write outside buffer bounds");
                    n || (n = "utf8");
                    for (var s = !1; ;) switch (n) {
                        case"hex":
                            return l(this, e, t, r);
                        case"utf8":
                        case"utf-8":
                            return f(this, e, t, r);
                        case"ascii":
                            return p(this, e, t, r);
                        case"latin1":
                        case"binary":
                            return m(this, e, t, r);
                        case"base64":
                            return g(this, e, t, r);
                        case"ucs2":
                        case"ucs-2":
                        case"utf16le":
                        case"utf-16le":
                            return _(this, e, t, r);
                        default:
                            if (s) throw new TypeError("Unknown encoding: " + n);
                            n = ("" + n).toLowerCase(), s = !0
                    }
                }, r.prototype.toJSON = function () {
                    return {type: "Buffer", data: Array.prototype.slice.call(this._arr || this, 0)}
                };
                var w = 4096;

                function E(e, t, r) {
                    var n = "";
                    r = Math.min(e.length, r);
                    for (var i = t; i < r; ++i) n += String.fromCharCode(127 & e[i]);
                    return n
                }

                function k(e, t, r) {
                    var n = "";
                    r = Math.min(e.length, r);
                    for (var i = t; i < r; ++i) n += String.fromCharCode(e[i]);
                    return n
                }

                function S(e, t, r) {
                    var n, i = e.length;
                    (!t || t < 0) && (t = 0), (!r || r < 0 || r > i) && (r = i);
                    for (var s = "", o = t; o < r; ++o) s += (n = e[o]) < 16 ? "0" + n.toString(16) : n.toString(16);
                    return s
                }

                function C(e, t, r) {
                    for (var n = e.slice(t, r), i = "", s = 0; s < n.length; s += 2) i += String.fromCharCode(n[s] + 256 * n[s + 1]);
                    return i
                }

                function x(e, t, r) {
                    if (e % 1 != 0 || e < 0) throw new RangeError("offset is not uint");
                    if (e + t > r) throw new RangeError("Trying to access beyond buffer length")
                }

                function A(e, t, n, i, s, o) {
                    if (!r.isBuffer(e)) throw new TypeError('"buffer" argument must be a Buffer instance');
                    if (t > s || t < o) throw new RangeError('"value" argument is out of bounds');
                    if (n + i > e.length) throw new RangeError("Index out of range")
                }

                function I(e, t, r, n, i, s) {
                    if (r + n > e.length) throw new RangeError("Index out of range");
                    if (r < 0) throw new RangeError("Index out of range")
                }

                function R(e, t, r, n, i) {
                    return t = +t, r >>>= 0, i || I(e, 0, r, 4), T.write(e, t, r, n, 23, 4), r + 4
                }

                function B(e, t, r, n, i) {
                    return t = +t, r >>>= 0, i || I(e, 0, r, 8), T.write(e, t, r, n, 52, 8), r + 8
                }

                r.prototype.slice = function (e, t) {
                    var n = this.length;
                    (e = ~~e) < 0 ? (e += n) < 0 && (e = 0) : e > n && (e = n), (t = void 0 === t ? n : ~~t) < 0 ? (t += n) < 0 && (t = 0) : t > n && (t = n), t < e && (t = e);
                    var i = this.subarray(e, t);
                    return i.__proto__ = r.prototype, i
                }, r.prototype.readUIntLE = function (e, t, r) {
                    e >>>= 0, t >>>= 0, r || x(e, t, this.length);
                    for (var n = this[e], i = 1, s = 0; ++s < t && (i *= 256);) n += this[e + s] * i;
                    return n
                }, r.prototype.readUIntBE = function (e, t, r) {
                    e >>>= 0, t >>>= 0, r || x(e, t, this.length);
                    for (var n = this[e + --t], i = 1; t > 0 && (i *= 256);) n += this[e + --t] * i;
                    return n
                }, r.prototype.readUInt8 = function (e, t) {
                    return e >>>= 0, t || x(e, 1, this.length), this[e]
                }, r.prototype.readUInt16LE = function (e, t) {
                    return e >>>= 0, t || x(e, 2, this.length), this[e] | this[e + 1] << 8
                }, r.prototype.readUInt16BE = function (e, t) {
                    return e >>>= 0, t || x(e, 2, this.length), this[e] << 8 | this[e + 1]
                }, r.prototype.readUInt32LE = function (e, t) {
                    return e >>>= 0, t || x(e, 4, this.length), (this[e] | this[e + 1] << 8 | this[e + 2] << 16) + 16777216 * this[e + 3]
                }, r.prototype.readUInt32BE = function (e, t) {
                    return e >>>= 0, t || x(e, 4, this.length), 16777216 * this[e] + (this[e + 1] << 16 | this[e + 2] << 8 | this[e + 3])
                }, r.prototype.readIntLE = function (e, t, r) {
                    e >>>= 0, t >>>= 0, r || x(e, t, this.length);
                    for (var n = this[e], i = 1, s = 0; ++s < t && (i *= 256);) n += this[e + s] * i;
                    return n >= (i *= 128) && (n -= Math.pow(2, 8 * t)), n
                }, r.prototype.readIntBE = function (e, t, r) {
                    e >>>= 0, t >>>= 0, r || x(e, t, this.length);
                    for (var n = t, i = 1, s = this[e + --n]; n > 0 && (i *= 256);) s += this[e + --n] * i;
                    return s >= (i *= 128) && (s -= Math.pow(2, 8 * t)), s
                }, r.prototype.readInt8 = function (e, t) {
                    return e >>>= 0, t || x(e, 1, this.length), 128 & this[e] ? -1 * (255 - this[e] + 1) : this[e]
                }, r.prototype.readInt16LE = function (e, t) {
                    e >>>= 0, t || x(e, 2, this.length);
                    var r = this[e] | this[e + 1] << 8;
                    return 32768 & r ? 4294901760 | r : r
                }, r.prototype.readInt16BE = function (e, t) {
                    e >>>= 0, t || x(e, 2, this.length);
                    var r = this[e + 1] | this[e] << 8;
                    return 32768 & r ? 4294901760 | r : r
                }, r.prototype.readInt32LE = function (e, t) {
                    return e >>>= 0, t || x(e, 4, this.length), this[e] | this[e + 1] << 8 | this[e + 2] << 16 | this[e + 3] << 24
                }, r.prototype.readInt32BE = function (e, t) {
                    return e >>>= 0, t || x(e, 4, this.length), this[e] << 24 | this[e + 1] << 16 | this[e + 2] << 8 | this[e + 3]
                }, r.prototype.readFloatLE = function (e, t) {
                    return e >>>= 0, t || x(e, 4, this.length), T.read(this, e, !0, 23, 4)
                }, r.prototype.readFloatBE = function (e, t) {
                    return e >>>= 0, t || x(e, 4, this.length), T.read(this, e, !1, 23, 4)
                }, r.prototype.readDoubleLE = function (e, t) {
                    return e >>>= 0, t || x(e, 8, this.length), T.read(this, e, !0, 52, 8)
                }, r.prototype.readDoubleBE = function (e, t) {
                    return e >>>= 0, t || x(e, 8, this.length), T.read(this, e, !1, 52, 8)
                }, r.prototype.writeUIntLE = function (e, t, r, n) {
                    e = +e, t >>>= 0, r >>>= 0, n || A(this, e, t, r, Math.pow(2, 8 * r) - 1, 0);
                    var i = 1, s = 0;
                    for (this[t] = 255 & e; ++s < r && (i *= 256);) this[t + s] = e / i & 255;
                    return t + r
                }, r.prototype.writeUIntBE = function (e, t, r, n) {
                    e = +e, t >>>= 0, r >>>= 0, n || A(this, e, t, r, Math.pow(2, 8 * r) - 1, 0);
                    var i = r - 1, s = 1;
                    for (this[t + i] = 255 & e; --i >= 0 && (s *= 256);) this[t + i] = e / s & 255;
                    return t + r
                }, r.prototype.writeUInt8 = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 1, 255, 0), this[t] = 255 & e, t + 1
                }, r.prototype.writeUInt16LE = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 2, 65535, 0), this[t] = 255 & e, this[t + 1] = e >>> 8, t + 2
                }, r.prototype.writeUInt16BE = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 2, 65535, 0), this[t] = e >>> 8, this[t + 1] = 255 & e, t + 2
                }, r.prototype.writeUInt32LE = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 4, 4294967295, 0), this[t + 3] = e >>> 24, this[t + 2] = e >>> 16, this[t + 1] = e >>> 8, this[t] = 255 & e, t + 4
                }, r.prototype.writeUInt32BE = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 4, 4294967295, 0), this[t] = e >>> 24, this[t + 1] = e >>> 16, this[t + 2] = e >>> 8, this[t + 3] = 255 & e, t + 4
                }, r.prototype.writeIntLE = function (e, t, r, n) {
                    if (e = +e, t >>>= 0, !n) {
                        var i = Math.pow(2, 8 * r - 1);
                        A(this, e, t, r, i - 1, -i)
                    }
                    var s = 0, o = 1, a = 0;
                    for (this[t] = 255 & e; ++s < r && (o *= 256);) e < 0 && 0 === a && 0 !== this[t + s - 1] && (a = 1), this[t + s] = (e / o >> 0) - a & 255;
                    return t + r
                }, r.prototype.writeIntBE = function (e, t, r, n) {
                    if (e = +e, t >>>= 0, !n) {
                        var i = Math.pow(2, 8 * r - 1);
                        A(this, e, t, r, i - 1, -i)
                    }
                    var s = r - 1, o = 1, a = 0;
                    for (this[t + s] = 255 & e; --s >= 0 && (o *= 256);) e < 0 && 0 === a && 0 !== this[t + s + 1] && (a = 1), this[t + s] = (e / o >> 0) - a & 255;
                    return t + r
                }, r.prototype.writeInt8 = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 1, 127, -128), e < 0 && (e = 255 + e + 1), this[t] = 255 & e, t + 1
                }, r.prototype.writeInt16LE = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 2, 32767, -32768), this[t] = 255 & e, this[t + 1] = e >>> 8, t + 2
                }, r.prototype.writeInt16BE = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 2, 32767, -32768), this[t] = e >>> 8, this[t + 1] = 255 & e, t + 2
                }, r.prototype.writeInt32LE = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 4, 2147483647, -2147483648), this[t] = 255 & e, this[t + 1] = e >>> 8, this[t + 2] = e >>> 16, this[t + 3] = e >>> 24, t + 4
                }, r.prototype.writeInt32BE = function (e, t, r) {
                    return e = +e, t >>>= 0, r || A(this, e, t, 4, 2147483647, -2147483648), e < 0 && (e = 4294967295 + e + 1), this[t] = e >>> 24, this[t + 1] = e >>> 16, this[t + 2] = e >>> 8, this[t + 3] = 255 & e, t + 4
                }, r.prototype.writeFloatLE = function (e, t, r) {
                    return R(this, e, t, !0, r)
                }, r.prototype.writeFloatBE = function (e, t, r) {
                    return R(this, e, t, !1, r)
                }, r.prototype.writeDoubleLE = function (e, t, r) {
                    return B(this, e, t, !0, r)
                }, r.prototype.writeDoubleBE = function (e, t, r) {
                    return B(this, e, t, !1, r)
                }, r.prototype.copy = function (e, t, n, i) {
                    if (!r.isBuffer(e)) throw new TypeError("argument should be a Buffer");
                    if (n || (n = 0), i || 0 === i || (i = this.length), t >= e.length && (t = e.length), t || (t = 0), i > 0 && i < n && (i = n), i === n) return 0;
                    if (0 === e.length || 0 === this.length) return 0;
                    if (t < 0) throw new RangeError("targetStart out of bounds");
                    if (n < 0 || n >= this.length) throw new RangeError("Index out of range");
                    if (i < 0) throw new RangeError("sourceEnd out of bounds");
                    i > this.length && (i = this.length), e.length - t < i - n && (i = e.length - t + n);
                    var s = i - n;
                    if (this === e && "function" == typeof Uint8Array.prototype.copyWithin) this.copyWithin(t, n, i); else if (this === e && n < t && t < i) for (var o = s - 1; o >= 0; --o) e[o + t] = this[o + n]; else Uint8Array.prototype.set.call(e, this.subarray(n, i), t);
                    return s
                }, r.prototype.fill = function (e, t, n, i) {
                    if ("string" == typeof e) {
                        if ("string" == typeof t ? (i = t, t = 0, n = this.length) : "string" == typeof n && (i = n, n = this.length), void 0 !== i && "string" != typeof i) throw new TypeError("encoding must be a string");
                        if ("string" == typeof i && !r.isEncoding(i)) throw new TypeError("Unknown encoding: " + i);
                        if (1 === e.length) {
                            var s = e.charCodeAt(0);
                            ("utf8" === i && s < 128 || "latin1" === i) && (e = s)
                        }
                    } else "number" == typeof e && (e &= 255);
                    if (t < 0 || this.length < t || this.length < n) throw new RangeError("Out of range index");
                    if (n <= t) return this;
                    var o;
                    if (t >>>= 0, n = void 0 === n ? this.length : n >>> 0, e || (e = 0), "number" == typeof e) for (o = t; o < n; ++o) this[o] = e; else {
                        var a = r.isBuffer(e) ? e : r.from(e, i), h = a.length;
                        if (0 === h) throw new TypeError('The value "' + e + '" is invalid for argument "value"');
                        for (o = 0; o < n - t; ++o) this[o + t] = a[o % h]
                    }
                    return this
                };
                var L = /[^+/0-9A-Za-z-_]/g;

                function O(e, t) {
                    var r;
                    t = t || 1 / 0;
                    for (var n = e.length, i = null, s = [], o = 0; o < n; ++o) {
                        if ((r = e.charCodeAt(o)) > 55295 && r < 57344) {
                            if (!i) {
                                if (r > 56319) {
                                    (t -= 3) > -1 && s.push(239, 191, 189);
                                    continue
                                }
                                if (o + 1 === n) {
                                    (t -= 3) > -1 && s.push(239, 191, 189);
                                    continue
                                }
                                i = r;
                                continue
                            }
                            if (r < 56320) {
                                (t -= 3) > -1 && s.push(239, 191, 189), i = r;
                                continue
                            }
                            r = 65536 + (i - 55296 << 10 | r - 56320)
                        } else i && (t -= 3) > -1 && s.push(239, 191, 189);
                        if (i = null, r < 128) {
                            if ((t -= 1) < 0) break;
                            s.push(r)
                        } else if (r < 2048) {
                            if ((t -= 2) < 0) break;
                            s.push(r >> 6 | 192, 63 & r | 128)
                        } else if (r < 65536) {
                            if ((t -= 3) < 0) break;
                            s.push(r >> 12 | 224, r >> 6 & 63 | 128, 63 & r | 128)
                        } else {
                            if (!(r < 1114112)) throw new Error("Invalid code point");
                            if ((t -= 4) < 0) break;
                            s.push(r >> 18 | 240, r >> 12 & 63 | 128, r >> 6 & 63 | 128, 63 & r | 128)
                        }
                    }
                    return s
                }

                function U(e) {
                    return b.toByteArray(function (e) {
                        if ((e = (e = e.split("=")[0]).trim().replace(L, "")).length < 2) return "";
                        for (; e.length % 4 != 0;) e += "=";
                        return e
                    }(e))
                }

                function P(e, t, r, n) {
                    for (var i = 0; i < n && !(i + r >= t.length || i >= e.length); ++i) t[i + r] = e[i];
                    return i
                }

                function M(e, t) {
                    return e instanceof t || null != e && null != e.constructor && null != e.constructor.name && e.constructor.name === t.name
                }

                function D(e) {
                    return e != e
                }
            }).call(this)
        }).call(this, y({}).Buffer)
    })), b = {
        toByteArray: function (e) {
            var t, r, n = x(e), i = n[0], s = n[1], o = new E(function (e, t, r) {
                return 3 * (t + r) / 4 - r
            }(0, i, s)), a = 0, h = s > 0 ? i - 4 : i;
            for (r = 0; r < h; r += 4) t = w[e.charCodeAt(r)] << 18 | w[e.charCodeAt(r + 1)] << 12 | w[e.charCodeAt(r + 2)] << 6 | w[e.charCodeAt(r + 3)], o[a++] = t >> 16 & 255, o[a++] = t >> 8 & 255, o[a++] = 255 & t;
            return 2 === s && (t = w[e.charCodeAt(r)] << 2 | w[e.charCodeAt(r + 1)] >> 4, o[a++] = 255 & t), 1 === s && (t = w[e.charCodeAt(r)] << 10 | w[e.charCodeAt(r + 1)] << 4 | w[e.charCodeAt(r + 2)] >> 2, o[a++] = t >> 8 & 255, o[a++] = 255 & t), o
        }, fromByteArray: function (e) {
            for (var t, r = e.length, n = r % 3, i = [], s = 0, o = r - n; s < o; s += 16383) i.push(A(e, s, s + 16383 > o ? o : s + 16383));
            return 1 === n ? (t = e[r - 1], i.push(v[t >> 2] + v[t << 4 & 63] + "==")) : 2 === n && (t = (e[r - 2] << 8) + e[r - 1], i.push(v[t >> 10] + v[t >> 4 & 63] + v[t << 2 & 63] + "=")), i.join("")
        }
    }, v = [], w = [], E = "undefined" != typeof Uint8Array ? Uint8Array : Array, k = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", S = 0, C = k.length; S < C; ++S) v[S] = k[S], w[k.charCodeAt(S)] = S;

    function x(e) {
        var t = e.length;
        if (t % 4 > 0) throw new Error("Invalid string. Length must be a multiple of 4");
        var r = e.indexOf("=");
        return -1 === r && (r = t), [r, r === t ? 0 : 4 - r % 4]
    }

    function A(e, t, r) {
        for (var n, i, s = [], o = t; o < r; o += 3) n = (e[o] << 16 & 16711680) + (e[o + 1] << 8 & 65280) + (255 & e[o + 2]), s.push(v[(i = n) >> 18 & 63] + v[i >> 12 & 63] + v[i >> 6 & 63] + v[63 & i]);
        return s.join("")
    }

    w["-".charCodeAt(0)] = 62, w["_".charCodeAt(0)] = 63;
    var T = {
        read: function (e, t, r, n, i) {
            var s, o, a = 8 * i - n - 1, h = (1 << a) - 1, u = h >> 1, c = -7, d = r ? i - 1 : 0, l = r ? -1 : 1,
                f = e[t + d];
            for (d += l, s = f & (1 << -c) - 1, f >>= -c, c += a; c > 0; s = 256 * s + e[t + d], d += l, c -= 8) ;
            for (o = s & (1 << -c) - 1, s >>= -c, c += n; c > 0; o = 256 * o + e[t + d], d += l, c -= 8) ;
            if (0 === s) s = 1 - u; else {
                if (s === h) return o ? NaN : 1 / 0 * (f ? -1 : 1);
                o += Math.pow(2, n), s -= u
            }
            return (f ? -1 : 1) * o * Math.pow(2, s - n)
        }, write: function (e, t, r, n, i, s) {
            var o, a, h, u = 8 * s - i - 1, c = (1 << u) - 1, d = c >> 1,
                l = 23 === i ? Math.pow(2, -24) - Math.pow(2, -77) : 0, f = n ? 0 : s - 1, p = n ? 1 : -1,
                m = t < 0 || 0 === t && 1 / t < 0 ? 1 : 0;
            for (t = Math.abs(t), isNaN(t) || t === 1 / 0 ? (a = isNaN(t) ? 1 : 0, o = c) : (o = Math.floor(Math.log(t) / Math.LN2), t * (h = Math.pow(2, -o)) < 1 && (o--, h *= 2), (t += o + d >= 1 ? l / h : l * Math.pow(2, 1 - d)) * h >= 2 && (o++, h /= 2), o + d >= c ? (a = 0, o = c) : o + d >= 1 ? (a = (t * h - 1) * Math.pow(2, i), o += d) : (a = t * Math.pow(2, d - 1) * Math.pow(2, i), o = 0)); i >= 8; e[r + f] = 255 & a, f += p, a /= 256, i -= 8) ;
            for (o = o << i | a, u += i; u > 0; e[r + f] = 255 & o, f += p, o /= 256, u -= 8) ;
            e[r + f - p] |= 128 * m
        }
    }, I = {}, R = y({}), B = R.Buffer;

    function L(e, t) {
        for (var r in e) t[r] = e[r]
    }

    function O(e, t, r) {
        return B(e, t, r)
    }

    B.from && B.alloc && B.allocUnsafe && B.allocUnsafeSlow ? I = R : (L(R, I), I.Buffer = O), L(B, O), O.from = function (e, t, r) {
        if ("number" == typeof e) throw new TypeError("Argument must not be a number");
        return B(e, t, r)
    }, O.alloc = function (e, t, r) {
        if ("number" != typeof e) throw new TypeError("Argument must be a number");
        var n = B(e);
        return void 0 !== t ? "string" == typeof r ? n.fill(t, r) : n.fill(t) : n.fill(0), n
    }, O.allocUnsafe = function (e) {
        if ("number" != typeof e) throw new TypeError("Argument must be a number");
        return B(e)
    }, O.allocUnsafeSlow = function (e) {
        if ("number" != typeof e) throw new TypeError("Argument must be a number");
        return R.SlowBuffer(e)
    };
    var U, P = I.Buffer;

    function M(e, t, r) {
        var n = [], i = null;
        return M._encode(n, e), i = P.concat(n), M.bytes = i.length, P.isBuffer(t) ? (i.copy(t, r), t) : i
    }

    M.bytes = -1, M._floatConversionDetected = !1, M.getType = function (e) {
        return P.isBuffer(e) ? "buffer" : Array.isArray(e) ? "array" : ArrayBuffer.isView(e) ? "arraybufferview" : e instanceof Number ? "number" : e instanceof Boolean ? "boolean" : e instanceof ArrayBuffer ? "arraybuffer" : typeof e
    }, M._encode = function (e, t) {
        if (null != t) switch (M.getType(t)) {
            case"buffer":
                M.buffer(e, t);
                break;
            case"object":
                M.dict(e, t);
                break;
            case"array":
                M.list(e, t);
                break;
            case"string":
                M.string(e, t);
                break;
            case"number":
            case"boolean":
                M.number(e, t);
                break;
            case"arraybufferview":
                M.buffer(e, P.from(t.buffer, t.byteOffset, t.byteLength));
                break;
            case"arraybuffer":
                M.buffer(e, P.from(t))
        }
    };
    var D = P.from("e"), N = P.from("d"), j = P.from("l");
    M.buffer = function (e, t) {
        e.push(P.from(t.length + ":"), t)
    }, M.string = function (e, t) {
        e.push(P.from(P.byteLength(t) + ":" + t))
    }, M.number = function (e, t) {
        var r = 2147483648 * (t / 2147483648 << 0) + (t % 2147483648 << 0);
        e.push(P.from("i" + r + "e")), r === t || M._floatConversionDetected || (M._floatConversionDetected = !0, console.warn('WARNING: Possible data corruption detected with value "' + t + '":', 'Bencoding only defines support for integers, value was converted to "' + r + '"'), console.trace())
    }, M.dict = function (e, t) {
        e.push(N);
        for (var r, n = 0, i = Object.keys(t).sort(), s = i.length; n < s; n++) null != t[r = i[n]] && (M.string(e, r), M._encode(e, t[r]));
        e.push(D)
    }, M.list = function (e, t) {
        var r = 0, n = t.length;
        for (e.push(j); r < n; r++) null != t[r] && M._encode(e, t[r]);
        e.push(D)
    }, U = M;
    var F, z = I.Buffer;

    function H(e, t, r) {
        for (var n = 0, i = 1, s = t; s < r; s++) {
            var o = e[s];
            if (o < 58 && o >= 48) n = 10 * n + (o - 48); else if (s !== t || 43 !== o) {
                if (s !== t || 45 !== o) {
                    if (46 === o) break;
                    throw new Error("not a number: buffer[" + s + "] = " + o)
                }
                i = -1
            }
        }
        return n * i
    }

    function W(e, t, r, n) {
        return null == e || 0 === e.length ? null : ("number" != typeof t && null == n && (n = t, t = void 0), "number" != typeof r && null == n && (n = r, r = void 0), W.position = 0, W.encoding = n || null, W.data = z.isBuffer(e) ? e.slice(t, r) : z.from(e), W.bytes = W.data.length, W.next())
    }

    W.bytes = 0, W.position = 0, W.data = null, W.encoding = null, W.next = function () {
        switch (W.data[W.position]) {
            case 100:
                return W.dictionary();
            case 108:
                return W.list();
            case 105:
                return W.integer();
            default:
                return W.buffer()
        }
    }, W.find = function (e) {
        for (var t = W.position, r = W.data.length, n = W.data; t < r;) {
            if (n[t] === e) return t;
            t++
        }
        throw new Error('Invalid data: Missing delimiter "' + String.fromCharCode(e) + '" [0x' + e.toString(16) + "]")
    }, W.dictionary = function () {
        W.position++;
        for (var e = {}; 101 !== W.data[W.position];) e[W.buffer()] = W.next();
        return W.position++, e
    }, W.list = function () {
        W.position++;
        for (var e = []; 101 !== W.data[W.position];) e.push(W.next());
        return W.position++, e
    }, W.integer = function () {
        var e = W.find(101), t = H(W.data, W.position + 1, e);
        return W.position += e + 1 - W.position, t
    }, W.buffer = function () {
        var e = W.find(58), t = H(W.data, W.position, e), r = ++e + t;
        return W.position = r, W.encoding ? W.data.toString(W.encoding, e, r) : W.data.slice(e, r)
    }, F = W;
    var q = {}, Z = q;
    Z.encode = U, Z.decode = F, Z.byteLength = Z.encodingLength = function (e) {
        return Z.encode(e).length
    };
    var V, $ = {}, K = "object" == typeof Reflect ? Reflect : null,
        G = K && "function" == typeof K.apply ? K.apply : function (e, t, r) {
            return Function.prototype.apply.call(e, t, r)
        };
    V = K && "function" == typeof K.ownKeys ? K.ownKeys : Object.getOwnPropertySymbols ? function (e) {
        return Object.getOwnPropertyNames(e).concat(Object.getOwnPropertySymbols(e))
    } : function (e) {
        return Object.getOwnPropertyNames(e)
    };
    var X = Number.isNaN || function (e) {
        return e != e
    };

    function Y() {
        Y.init.call(this)
    }

    ($ = Y).once = function (e, t) {
        return new Promise((function (r, n) {
            function i() {
                void 0 !== s && e.removeListener("error", s), r([].slice.call(arguments))
            }

            var s;
            "error" !== t && (s = function (r) {
                e.removeListener(t, i), n(r)
            }, e.once("error", s)), e.once(t, i)
        }))
    }, Y.EventEmitter = Y, Y.prototype._events = void 0, Y.prototype._eventsCount = 0, Y.prototype._maxListeners = void 0;
    var J = 10;

    function Q(e) {
        if ("function" != typeof e) throw new TypeError('The "listener" argument must be of type Function. Received type ' + typeof e)
    }

    function ee(e) {
        return void 0 === e._maxListeners ? Y.defaultMaxListeners : e._maxListeners
    }

    function te(e, t, r, n) {
        var i, s, o, a;
        if (Q(r), void 0 === (s = e._events) ? (s = e._events = Object.create(null), e._eventsCount = 0) : (void 0 !== s.newListener && (e.emit("newListener", t, r.listener ? r.listener : r), s = e._events), o = s[t]), void 0 === o) o = s[t] = r, ++e._eventsCount; else if ("function" == typeof o ? o = s[t] = n ? [r, o] : [o, r] : n ? o.unshift(r) : o.push(r), (i = ee(e)) > 0 && o.length > i && !o.warned) {
            o.warned = !0;
            var h = new Error("Possible EventEmitter memory leak detected. " + o.length + " " + String(t) + " listeners added. Use emitter.setMaxListeners() to increase limit");
            h.name = "MaxListenersExceededWarning", h.emitter = e, h.type = t, h.count = o.length, a = h, console && console.warn && console.warn(a)
        }
        return e
    }

    function re(e, t, r) {
        var n = {fired: !1, wrapFn: void 0, target: e, type: t, listener: r}, i = function () {
            if (!this.fired) return this.target.removeListener(this.type, this.wrapFn), this.fired = !0, 0 === arguments.length ? this.listener.call(this.target) : this.listener.apply(this.target, arguments)
        }.bind(n);
        return i.listener = r, n.wrapFn = i, i
    }

    function ne(e, t, r) {
        var n = e._events;
        if (void 0 === n) return [];
        var i = n[t];
        return void 0 === i ? [] : "function" == typeof i ? r ? [i.listener || i] : [i] : r ? function (e) {
            for (var t = new Array(e.length), r = 0; r < t.length; ++r) t[r] = e[r].listener || e[r];
            return t
        }(i) : se(i, i.length)
    }

    function ie(e) {
        var t = this._events;
        if (void 0 !== t) {
            var r = t[e];
            if ("function" == typeof r) return 1;
            if (void 0 !== r) return r.length
        }
        return 0
    }

    function se(e, t) {
        for (var r = new Array(t), n = 0; n < t; ++n) r[n] = e[n];
        return r
    }

    Object.defineProperty(Y, "defaultMaxListeners", {
        enumerable: !0, get: function () {
            return J
        }, set: function (e) {
            if ("number" != typeof e || e < 0 || X(e)) throw new RangeError('The value of "defaultMaxListeners" is out of range. It must be a non-negative number. Received ' + e + ".");
            J = e
        }
    }), Y.init = function () {
        void 0 !== this._events && this._events !== Object.getPrototypeOf(this)._events || (this._events = Object.create(null), this._eventsCount = 0), this._maxListeners = this._maxListeners || void 0
    }, Y.prototype.setMaxListeners = function (e) {
        if ("number" != typeof e || e < 0 || X(e)) throw new RangeError('The value of "n" is out of range. It must be a non-negative number. Received ' + e + ".");
        return this._maxListeners = e, this
    }, Y.prototype.getMaxListeners = function () {
        return ee(this)
    }, Y.prototype.emit = function (e) {
        for (var t = [], r = 1; r < arguments.length; r++) t.push(arguments[r]);
        var n = "error" === e, i = this._events;
        if (void 0 !== i) n = n && void 0 === i.error; else if (!n) return !1;
        if (n) {
            var s;
            if (t.length > 0 && (s = t[0]), s instanceof Error) throw s;
            var o = new Error("Unhandled error." + (s ? " (" + s.message + ")" : ""));
            throw o.context = s, o
        }
        var a = i[e];
        if (void 0 === a) return !1;
        if ("function" == typeof a) G(a, this, t); else {
            var h = a.length, u = se(a, h);
            for (r = 0; r < h; ++r) G(u[r], this, t)
        }
        return !0
    }, Y.prototype.addListener = function (e, t) {
        return te(this, e, t, !1)
    }, Y.prototype.on = Y.prototype.addListener, Y.prototype.prependListener = function (e, t) {
        return te(this, e, t, !0)
    }, Y.prototype.once = function (e, t) {
        return Q(t), this.on(e, re(this, e, t)), this
    }, Y.prototype.prependOnceListener = function (e, t) {
        return Q(t), this.prependListener(e, re(this, e, t)), this
    }, Y.prototype.removeListener = function (e, t) {
        var r, n, i, s, o;
        if (Q(t), void 0 === (n = this._events)) return this;
        if (void 0 === (r = n[e])) return this;
        if (r === t || r.listener === t) 0 == --this._eventsCount ? this._events = Object.create(null) : (delete n[e], n.removeListener && this.emit("removeListener", e, r.listener || t)); else if ("function" != typeof r) {
            for (i = -1, s = r.length - 1; s >= 0; s--) if (r[s] === t || r[s].listener === t) {
                o = r[s].listener, i = s;
                break
            }
            if (i < 0) return this;
            0 === i ? r.shift() : function (e, t) {
                for (; t + 1 < e.length; t++) e[t] = e[t + 1];
                e.pop()
            }(r, i), 1 === r.length && (n[e] = r[0]), void 0 !== n.removeListener && this.emit("removeListener", e, o || t)
        }
        return this
    }, Y.prototype.off = Y.prototype.removeListener, Y.prototype.removeAllListeners = function (e) {
        var t, r, n;
        if (void 0 === (r = this._events)) return this;
        if (void 0 === r.removeListener) return 0 === arguments.length ? (this._events = Object.create(null), this._eventsCount = 0) : void 0 !== r[e] && (0 == --this._eventsCount ? this._events = Object.create(null) : delete r[e]), this;
        if (0 === arguments.length) {
            var i, s = Object.keys(r);
            for (n = 0; n < s.length; ++n) "removeListener" !== (i = s[n]) && this.removeAllListeners(i);
            return this.removeAllListeners("removeListener"), this._events = Object.create(null), this._eventsCount = 0, this
        }
        if ("function" == typeof (t = r[e])) this.removeListener(e, t); else if (void 0 !== t) for (n = t.length - 1; n >= 0; n--) this.removeListener(e, t[n]);
        return this
    }, Y.prototype.listeners = function (e) {
        return ne(this, e, !0)
    }, Y.prototype.rawListeners = function (e) {
        return ne(this, e, !1)
    }, Y.listenerCount = function (e, t) {
        return "function" == typeof e.listenerCount ? e.listenerCount(t) : ie.call(e, t)
    }, Y.prototype.listenerCount = ie, Y.prototype.eventNames = function () {
        return this._eventsCount > 0 ? V(this._events) : []
    };
    var oe = $.EventEmitter, ae = {};

    function he(e, t) {
        var r = Object.keys(e);
        if (Object.getOwnPropertySymbols) {
            var n = Object.getOwnPropertySymbols(e);
            t && (n = n.filter((function (t) {
                return Object.getOwnPropertyDescriptor(e, t).enumerable
            }))), r.push.apply(r, n)
        }
        return r
    }

    function ue(e, t, r) {
        return t in e ? Object.defineProperty(e, t, {
            value: r,
            enumerable: !0,
            configurable: !0,
            writable: !0
        }) : e[t] = r, e
    }

    function ce(e, t) {
        for (var r = 0; r < t.length; r++) {
            var n = t[r];
            n.enumerable = n.enumerable || !1, n.configurable = !0, "value" in n && (n.writable = !0), Object.defineProperty(e, n.key, n)
        }
    }

    var de, le, fe = y({}).Buffer, pe = ae.inspect, me = pe && pe.custom || "inspect", ge = function () {
        function e() {
            !function (e, t) {
                if (!(e instanceof t)) throw new TypeError("Cannot call a class as a function")
            }(this, e), this.head = null, this.tail = null, this.length = 0
        }

        var t, r;
        return t = e, (r = [{
            key: "push", value: function (e) {
                var t = {data: e, next: null};
                this.length > 0 ? this.tail.next = t : this.head = t, this.tail = t, ++this.length
            }
        }, {
            key: "unshift", value: function (e) {
                var t = {data: e, next: this.head};
                0 === this.length && (this.tail = t), this.head = t, ++this.length
            }
        }, {
            key: "shift", value: function () {
                if (0 !== this.length) {
                    var e = this.head.data;
                    return 1 === this.length ? this.head = this.tail = null : this.head = this.head.next, --this.length, e
                }
            }
        }, {
            key: "clear", value: function () {
                this.head = this.tail = null, this.length = 0
            }
        }, {
            key: "join", value: function (e) {
                if (0 === this.length) return "";
                for (var t = this.head, r = "" + t.data; t = t.next;) r += e + t.data;
                return r
            }
        }, {
            key: "concat", value: function (e) {
                if (0 === this.length) return fe.alloc(0);
                for (var t, r, n, i = fe.allocUnsafe(e >>> 0), s = this.head, o = 0; s;) t = s.data, r = i, n = o, fe.prototype.copy.call(t, r, n), o += s.data.length, s = s.next;
                return i
            }
        }, {
            key: "consume", value: function (e, t) {
                var r;
                return e < this.head.data.length ? (r = this.head.data.slice(0, e), this.head.data = this.head.data.slice(e)) : r = e === this.head.data.length ? this.shift() : t ? this._getString(e) : this._getBuffer(e), r
            }
        }, {
            key: "first", value: function () {
                return this.head.data
            }
        }, {
            key: "_getString", value: function (e) {
                var t = this.head, r = 1, n = t.data;
                for (e -= n.length; t = t.next;) {
                    var i = t.data, s = e > i.length ? i.length : e;
                    if (s === i.length ? n += i : n += i.slice(0, e), 0 == (e -= s)) {
                        s === i.length ? (++r, t.next ? this.head = t.next : this.head = this.tail = null) : (this.head = t, t.data = i.slice(s));
                        break
                    }
                    ++r
                }
                return this.length -= r, n
            }
        }, {
            key: "_getBuffer", value: function (e) {
                var t = fe.allocUnsafe(e), r = this.head, n = 1;
                for (r.data.copy(t), e -= r.data.length; r = r.next;) {
                    var i = r.data, s = e > i.length ? i.length : e;
                    if (i.copy(t, t.length - e, 0, s), 0 == (e -= s)) {
                        s === i.length ? (++n, r.next ? this.head = r.next : this.head = this.tail = null) : (this.head = r, r.data = i.slice(s));
                        break
                    }
                    ++n
                }
                return this.length -= n, t
            }
        }, {
            key: me, value: function (e, t) {
                return pe(this, function (e) {
                    for (var t = 1; t < arguments.length; t++) {
                        var r = null != arguments[t] ? arguments[t] : {};
                        t % 2 ? he(Object(r), !0).forEach((function (t) {
                            ue(e, t, r[t])
                        })) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(r)) : he(Object(r)).forEach((function (t) {
                            Object.defineProperty(e, t, Object.getOwnPropertyDescriptor(r, t))
                        }))
                    }
                    return e
                }({}, t, {depth: 0, customInspect: !1}))
            }
        }]) && ce(t.prototype, r), e
    }(), _e = {}, ye = _e = {};

    function be() {
        throw new Error("setTimeout has not been defined")
    }

    function ve() {
        throw new Error("clearTimeout has not been defined")
    }

    function we(e) {
        if (de === setTimeout) return setTimeout(e, 0);
        if ((de === be || !de) && setTimeout) return de = setTimeout, setTimeout(e, 0);
        try {
            return de(e, 0)
        } catch (t) {
            try {
                return de.call(null, e, 0)
            } catch (t) {
                return de.call(this, e, 0)
            }
        }
    }

    !function () {
        try {
            de = "function" == typeof setTimeout ? setTimeout : be
        } catch (e) {
            de = be
        }
        try {
            le = "function" == typeof clearTimeout ? clearTimeout : ve
        } catch (e) {
            le = ve
        }
    }();
    var Ee, ke = [], Se = !1, Ce = -1;

    function xe() {
        Se && Ee && (Se = !1, Ee.length ? ke = Ee.concat(ke) : Ce = -1, ke.length && Ae())
    }

    function Ae() {
        if (!Se) {
            var e = we(xe);
            Se = !0;
            for (var t = ke.length; t;) {
                for (Ee = ke, ke = []; ++Ce < t;) Ee && Ee[Ce].run();
                Ce = -1, t = ke.length
            }
            Ee = null, Se = !1, function (e) {
                if (le === clearTimeout) return clearTimeout(e);
                if ((le === ve || !le) && clearTimeout) return le = clearTimeout, clearTimeout(e);
                try {
                    le(e)
                } catch (t) {
                    try {
                        return le.call(null, e)
                    } catch (t) {
                        return le.call(this, e)
                    }
                }
            }(e)
        }
    }

    function Te(e, t) {
        this.fun = e, this.array = t
    }

    function Ie() {
    }

    ye.nextTick = function (e) {
        var t = new Array(arguments.length - 1);
        if (arguments.length > 1) for (var r = 1; r < arguments.length; r++) t[r - 1] = arguments[r];
        ke.push(new Te(e, t)), 1 !== ke.length || Se || we(Ae)
    }, Te.prototype.run = function () {
        this.fun.apply(null, this.array)
    }, ye.title = "browser", ye.browser = !0, ye.env = {}, ye.argv = [], ye.version = "", ye.versions = {}, ye.on = Ie, ye.addListener = Ie, ye.once = Ie, ye.off = Ie, ye.removeListener = Ie, ye.removeAllListeners = Ie, ye.emit = Ie, ye.prependListener = Ie, ye.prependOnceListener = Ie, ye.listeners = function (e) {
        return []
    }, ye.binding = function (e) {
        throw new Error("process.binding is not supported")
    }, ye.cwd = function () {
        return "/"
    }, ye.chdir = function (e) {
        throw new Error("process.chdir is not supported")
    }, ye.umask = function () {
        return 0
    };
    var Re = {};
    (function (e) {
        (function () {
            "use strict";

            function t(e, t) {
                n(e, t), r(e)
            }

            function r(e) {
                e._writableState && !e._writableState.emitClose || e._readableState && !e._readableState.emitClose || e.emit("close")
            }

            function n(e, t) {
                e.emit("error", t)
            }

            Re = {
                destroy: function (i, s) {
                    var o = this, a = this._readableState && this._readableState.destroyed,
                        h = this._writableState && this._writableState.destroyed;
                    return a || h ? (s ? s(i) : i && (this._writableState ? this._writableState.errorEmitted || (this._writableState.errorEmitted = !0, e.nextTick(n, this, i)) : e.nextTick(n, this, i)), this) : (this._readableState && (this._readableState.destroyed = !0), this._writableState && (this._writableState.destroyed = !0), this._destroy(i || null, (function (n) {
                        !s && n ? o._writableState ? o._writableState.errorEmitted ? e.nextTick(r, o) : (o._writableState.errorEmitted = !0, e.nextTick(t, o, n)) : e.nextTick(t, o, n) : s ? (e.nextTick(r, o), s(n)) : e.nextTick(r, o)
                    })), this)
                }, undestroy: function () {
                    this._readableState && (this._readableState.destroyed = !1, this._readableState.reading = !1, this._readableState.ended = !1, this._readableState.endEmitted = !1), this._writableState && (this._writableState.destroyed = !1, this._writableState.ended = !1, this._writableState.ending = !1, this._writableState.finalCalled = !1, this._writableState.prefinished = !1, this._writableState.finished = !1, this._writableState.errorEmitted = !1)
                }, errorOrDestroy: function (e, t) {
                    var r = e._readableState, n = e._writableState;
                    r && r.autoDestroy || n && n.autoDestroy ? e.destroy(t) : e.emit("error", t)
                }
            }
        }).call(this)
    }).call(this, _e);
    var Be = {}, Le = {};

    function Oe(e, t, r) {
        r || (r = Error);
        var n = function (e) {
            var r, n;

            function i(r, n, i) {
                return e.call(this, function (e, r, n) {
                    return "string" == typeof t ? t : t(e, r, n)
                }(r, n, i)) || this
            }

            return n = e, (r = i).prototype = Object.create(n.prototype), r.prototype.constructor = r, r.__proto__ = n, i
        }(r);
        n.prototype.name = r.name, n.prototype.code = e, Le[e] = n
    }

    function Ue(e, t) {
        if (Array.isArray(e)) {
            var r = e.length;
            return e = e.map((function (e) {
                return String(e)
            })), r > 2 ? "one of ".concat(t, " ").concat(e.slice(0, r - 1).join(", "), ", or ") + e[r - 1] : 2 === r ? "one of ".concat(t, " ").concat(e[0], " or ").concat(e[1]) : "of ".concat(t, " ").concat(e[0])
        }
        return "of ".concat(t, " ").concat(String(e))
    }

    Oe("ERR_INVALID_OPT_VALUE", (function (e, t) {
        return 'The value "' + t + '" is invalid for option "' + e + '"'
    }), TypeError), Oe("ERR_INVALID_ARG_TYPE", (function (e, t, r) {
        var n, i, s, o;
        if ("string" == typeof t && ("not ", "not " === t.substr(0, "not ".length)) ? (n = "must not be", t = t.replace(/^not /, "")) : n = "must be", s = e, (void 0 === o || o > s.length) && (o = s.length), " argument" === s.substring(o - " argument".length, o)) i = "The ".concat(e, " ").concat(n, " ").concat(Ue(t, "type")); else {
            var a = function (e, t, r) {
                return "number" != typeof r && (r = 0), !(r + ".".length > e.length) && -1 !== e.indexOf(".", r)
            }(e) ? "property" : "argument";
            i = 'The "'.concat(e, '" ').concat(a, " ").concat(n, " ").concat(Ue(t, "type"))
        }
        return i + ". Received type ".concat(typeof r)
    }), TypeError), Oe("ERR_STREAM_PUSH_AFTER_EOF", "stream.push() after EOF"), Oe("ERR_METHOD_NOT_IMPLEMENTED", (function (e) {
        return "The " + e + " method is not implemented"
    })), Oe("ERR_STREAM_PREMATURE_CLOSE", "Premature close"), Oe("ERR_STREAM_DESTROYED", (function (e) {
        return "Cannot call " + e + " after a stream was destroyed"
    })), Oe("ERR_MULTIPLE_CALLBACK", "Callback called multiple times"), Oe("ERR_STREAM_CANNOT_PIPE", "Cannot pipe, not readable"), Oe("ERR_STREAM_WRITE_AFTER_END", "write after end"), Oe("ERR_STREAM_NULL_VALUES", "May not write null values to stream", TypeError), Oe("ERR_UNKNOWN_ENCODING", (function (e) {
        return "Unknown encoding: " + e
    }), TypeError), Oe("ERR_STREAM_UNSHIFT_AFTER_END_EVENT", "stream.unshift() after end event"), Be.codes = Le;
    var Pe = Be.codes.ERR_INVALID_OPT_VALUE, Me = {
        getHighWaterMark: function (e, t, r, n) {
            var i = function (e, t, r) {
                return null != e.highWaterMark ? e.highWaterMark : t ? e[r] : null
            }(t, n, r);
            if (null != i) {
                if (!isFinite(i) || Math.floor(i) !== i || i < 0) throw new Pe(n ? r : "highWaterMark", i);
                return Math.floor(i)
            }
            return e.objectMode ? 16 : 16384
        }
    }, De = {};
    De = "function" == typeof Object.create ? function (e, t) {
        t && (e.super_ = t, e.prototype = Object.create(t.prototype, {
            constructor: {
                value: e,
                enumerable: !1,
                writable: !0,
                configurable: !0
            }
        }))
    } : function (e, t) {
        if (t) {
            e.super_ = t;
            var r = function () {
            };
            r.prototype = t.prototype, e.prototype = new r, e.prototype.constructor = e
        }
    };
    var Ne = {};
    (function (e) {
        (function () {
            function t(t) {
                try {
                    if (!e.localStorage) return !1
                } catch (n) {
                    return !1
                }
                var r = e.localStorage[t];
                return null != r && "true" === String(r).toLowerCase()
            }

            Ne = function (e, r) {
                if (t("noDeprecation")) return e;
                var n = !1;
                return function () {
                    if (!n) {
                        if (t("throwDeprecation")) throw new Error(r);
                        t("traceDeprecation") ? console.trace(r) : console.warn(r), n = !0
                    }
                    return e.apply(this, arguments)
                }
            }
        }).call(this)
    }).call(this, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {});
    var je = {}, Fe = y({}), ze = Fe.Buffer;

    function He(e, t) {
        for (var r in e) t[r] = e[r]
    }

    function We(e, t, r) {
        return ze(e, t, r)
    }

    ze.from && ze.alloc && ze.allocUnsafe && ze.allocUnsafeSlow ? je = Fe : (He(Fe, je), je.Buffer = We), We.prototype = Object.create(ze.prototype), He(ze, We), We.from = function (e, t, r) {
        if ("number" == typeof e) throw new TypeError("Argument must not be a number");
        return ze(e, t, r)
    }, We.alloc = function (e, t, r) {
        if ("number" != typeof e) throw new TypeError("Argument must be a number");
        var n = ze(e);
        return void 0 !== t ? "string" == typeof r ? n.fill(t, r) : n.fill(t) : n.fill(0), n
    }, We.allocUnsafe = function (e) {
        if ("number" != typeof e) throw new TypeError("Argument must be a number");
        return ze(e)
    }, We.allocUnsafeSlow = function (e) {
        if ("number" != typeof e) throw new TypeError("Argument must be a number");
        return Fe.SlowBuffer(e)
    };
    var qe = Je, Ze = Be.codes, Ve = Ze.ERR_METHOD_NOT_IMPLEMENTED, $e = Ze.ERR_MULTIPLE_CALLBACK,
        Ke = Ze.ERR_TRANSFORM_ALREADY_TRANSFORMING, Ge = Ze.ERR_TRANSFORM_WITH_LENGTH_0, Xe = g({});

    function Ye(e, t) {
        var r = this._transformState;
        r.transforming = !1;
        var n = r.writecb;
        if (null === n) return this.emit("error", new $e);
        r.writechunk = null, r.writecb = null, null != t && this.push(t), n(e);
        var i = this._readableState;
        i.reading = !1, (i.needReadable || i.length < i.highWaterMark) && this._read(i.highWaterMark)
    }

    function Je(e) {
        if (!(this instanceof Je)) return new Je(e);
        Xe.call(this, e), this._transformState = {
            afterTransform: Ye.bind(this),
            needTransform: !1,
            transforming: !1,
            writecb: null,
            writechunk: null,
            writeencoding: null
        }, this._readableState.needReadable = !0, this._readableState.sync = !1, e && ("function" == typeof e.transform && (this._transform = e.transform), "function" == typeof e.flush && (this._flush = e.flush)), this.on("prefinish", Qe)
    }

    function Qe() {
        var e = this;
        "function" != typeof this._flush || this._readableState.destroyed ? et(this, null, null) : this._flush((function (t, r) {
            et(e, t, r)
        }))
    }

    function et(e, t, r) {
        if (t) return e.emit("error", t);
        if (null != r && e.push(r), e._writableState.length) throw new Ge;
        if (e._transformState.transforming) throw new Ke;
        return e.push(null)
    }

    De(Je, Xe), Je.prototype.push = function (e, t) {
        return this._transformState.needTransform = !1, Xe.prototype.push.call(this, e, t)
    }, Je.prototype._transform = function (e, t, r) {
        r(new Ve("_transform()"))
    }, Je.prototype._write = function (e, t, r) {
        var n = this._transformState;
        if (n.writecb = r, n.writechunk = e, n.writeencoding = t, !n.transforming) {
            var i = this._readableState;
            (n.needTransform || i.needReadable || i.length < i.highWaterMark) && this._read(i.highWaterMark)
        }
    }, Je.prototype._read = function (e) {
        var t = this._transformState;
        null === t.writechunk || t.transforming ? t.needTransform = !0 : (t.transforming = !0, this._transform(t.writechunk, t.writeencoding, t.afterTransform))
    }, Je.prototype._destroy = function (e, t) {
        Xe.prototype._destroy.call(this, e, (function (e) {
            t(e)
        }))
    };
    var tt, rt = nt;

    function nt(e) {
        if (!(this instanceof nt)) return new nt(e);
        qe.call(this, e)
    }

    De(nt, qe), nt.prototype._transform = function (e, t, r) {
        r(null, e)
    };
    var it = Be.codes, st = it.ERR_MISSING_ARGS, ot = it.ERR_STREAM_DESTROYED;

    function at(e) {
        if (e) throw e
    }

    function ht(e) {
        e()
    }

    function ut(e, t) {
        return e.pipe(t)
    }

    var ct = function () {
        for (var e = arguments.length, t = new Array(e), r = 0; r < e; r++) t[r] = arguments[r];
        var n, i = function (e) {
            return e.length ? "function" != typeof e[e.length - 1] ? at : e.pop() : at
        }(t);
        if (Array.isArray(t[0]) && (t = t[0]), t.length < 2) throw new st("streams");
        var s = t.map((function (e, r) {
            var o = r < t.length - 1;
            return function (e, t, r, n) {
                n = function (e) {
                    var t = !1;
                    return function () {
                        t || (t = !0, e.apply(void 0, arguments))
                    }
                }(n);
                var i = !1;
                e.on("close", (function () {
                    i = !0
                })), void 0 === tt && (tt = p({})), tt(e, {readable: t, writable: r}, (function (e) {
                    if (e) return n(e);
                    i = !0, n()
                }));
                var s = !1;
                return function (t) {
                    if (!i && !s) return s = !0, function (e) {
                        return e.setHeader && "function" == typeof e.abort
                    }(e) ? e.abort() : "function" == typeof e.destroy ? e.destroy() : void n(t || new ot("pipe"))
                }
            }(e, o, r > 0, (function (e) {
                n || (n = e), e && s.forEach(ht), o || (s.forEach(ht), i(n))
            }))
        }));
        return t.reduce(ut)
    }, dt = {};
    (dt = dt = d({})).Stream = dt, dt.Readable = dt, dt.Writable = _({}), dt.Duplex = g({}), dt.Transform = qe, dt.PassThrough = rt, dt.finished = p({}), dt.pipeline = ct;
    var lt = {};
    (function (e) {
        (function () {
            const {Transform: t} = dt;
            lt = class extends t {
                constructor(e, t = {}) {
                    super(t), "object" == typeof e && (e = (t = e).size), this.size = e || 512;
                    const {nopad: r, zeroPadding: n = !0} = t;
                    this._zeroPadding = !r && !!n, this._buffered = [], this._bufferedBytes = 0
                }

                _transform(t, r, n) {
                    for (this._bufferedBytes += t.length, this._buffered.push(t); this._bufferedBytes >= this.size;) {
                        const t = e.concat(this._buffered);
                        this._bufferedBytes -= this.size, this.push(t.slice(0, this.size)), this._buffered = [t.slice(this.size, t.length)]
                    }
                    n()
                }

                _flush() {
                    if (this._bufferedBytes && this._zeroPadding) {
                        const t = e.alloc(this.size - this._bufferedBytes);
                        this._buffered.push(t), this.push(e.concat(this._buffered)), this._buffered = null
                    } else this._bufferedBytes && (this.push(e.concat(this._buffered)), this._buffered = null);
                    this.push(null)
                }
            }
        }).call(this)
    }).call(this, y({}).Buffer);
    var ft = {};
    (function (e) {
        (function () {
            "use strict";

            function t(e) {
                if ("string" != typeof e) throw new TypeError("Path must be a string. Received " + JSON.stringify(e))
            }

            function r(e, t) {
                for (var r, n = "", i = 0, s = -1, o = 0, a = 0; a <= e.length; ++a) {
                    if (a < e.length) r = e.charCodeAt(a); else {
                        if (47 === r) break;
                        r = 47
                    }
                    if (47 === r) {
                        if (s === a - 1 || 1 === o) ; else if (s !== a - 1 && 2 === o) {
                            if (n.length < 2 || 2 !== i || 46 !== n.charCodeAt(n.length - 1) || 46 !== n.charCodeAt(n.length - 2)) if (n.length > 2) {
                                var h = n.lastIndexOf("/");
                                if (h !== n.length - 1) {
                                    -1 === h ? (n = "", i = 0) : i = (n = n.slice(0, h)).length - 1 - n.lastIndexOf("/"), s = a, o = 0;
                                    continue
                                }
                            } else if (2 === n.length || 1 === n.length) {
                                n = "", i = 0, s = a, o = 0;
                                continue
                            }
                            t && (n.length > 0 ? n += "/.." : n = "..", i = 2)
                        } else n.length > 0 ? n += "/" + e.slice(s + 1, a) : n = e.slice(s + 1, a), i = a - s - 1;
                        s = a, o = 0
                    } else 46 === r && -1 !== o ? ++o : o = -1
                }
                return n
            }

            var n = {
                resolve: function () {
                    for (var n, i = "", s = !1, o = arguments.length - 1; o >= -1 && !s; o--) {
                        var a;
                        o >= 0 ? a = arguments[o] : (void 0 === n && (n = e.cwd()), a = n), t(a), 0 !== a.length && (i = a + "/" + i, s = 47 === a.charCodeAt(0))
                    }
                    return i = r(i, !s), s ? i.length > 0 ? "/" + i : "/" : i.length > 0 ? i : "."
                }, normalize: function (e) {
                    if (t(e), 0 === e.length) return ".";
                    var n = 47 === e.charCodeAt(0), i = 47 === e.charCodeAt(e.length - 1);
                    return 0 !== (e = r(e, !n)).length || n || (e = "."), e.length > 0 && i && (e += "/"), n ? "/" + e : e
                }, isAbsolute: function (e) {
                    return t(e), e.length > 0 && 47 === e.charCodeAt(0)
                }, join: function () {
                    if (0 === arguments.length) return ".";
                    for (var e, r = 0; r < arguments.length; ++r) {
                        var i = arguments[r];
                        t(i), i.length > 0 && (void 0 === e ? e = i : e += "/" + i)
                    }
                    return void 0 === e ? "." : n.normalize(e)
                }, relative: function (e, r) {
                    if (t(e), t(r), e === r) return "";
                    if ((e = n.resolve(e)) === (r = n.resolve(r))) return "";
                    for (var i = 1; i < e.length && 47 === e.charCodeAt(i); ++i) ;
                    for (var s = e.length, o = s - i, a = 1; a < r.length && 47 === r.charCodeAt(a); ++a) ;
                    for (var h = r.length - a, u = o < h ? o : h, c = -1, d = 0; d <= u; ++d) {
                        if (d === u) {
                            if (h > u) {
                                if (47 === r.charCodeAt(a + d)) return r.slice(a + d + 1);
                                if (0 === d) return r.slice(a + d)
                            } else o > u && (47 === e.charCodeAt(i + d) ? c = d : 0 === d && (c = 0));
                            break
                        }
                        var l = e.charCodeAt(i + d);
                        if (l !== r.charCodeAt(a + d)) break;
                        47 === l && (c = d)
                    }
                    var f = "";
                    for (d = i + c + 1; d <= s; ++d) d !== s && 47 !== e.charCodeAt(d) || (0 === f.length ? f += ".." : f += "/..");
                    return f.length > 0 ? f + r.slice(a + c) : (a += c, 47 === r.charCodeAt(a) && ++a, r.slice(a))
                }, _makeLong: function (e) {
                    return e
                }, dirname: function (e) {
                    if (t(e), 0 === e.length) return ".";
                    for (var r = e.charCodeAt(0), n = 47 === r, i = -1, s = !0, o = e.length - 1; o >= 1; --o) if (47 === (r = e.charCodeAt(o))) {
                        if (!s) {
                            i = o;
                            break
                        }
                    } else s = !1;
                    return -1 === i ? n ? "/" : "." : n && 1 === i ? "//" : e.slice(0, i)
                }, basename: function (e, r) {
                    if (void 0 !== r && "string" != typeof r) throw new TypeError('"ext" argument must be a string');
                    t(e);
                    var n, i = 0, s = -1, o = !0;
                    if (void 0 !== r && r.length > 0 && r.length <= e.length) {
                        if (r.length === e.length && r === e) return "";
                        var a = r.length - 1, h = -1;
                        for (n = e.length - 1; n >= 0; --n) {
                            var u = e.charCodeAt(n);
                            if (47 === u) {
                                if (!o) {
                                    i = n + 1;
                                    break
                                }
                            } else -1 === h && (o = !1, h = n + 1), a >= 0 && (u === r.charCodeAt(a) ? -1 == --a && (s = n) : (a = -1, s = h))
                        }
                        return i === s ? s = h : -1 === s && (s = e.length), e.slice(i, s)
                    }
                    for (n = e.length - 1; n >= 0; --n) if (47 === e.charCodeAt(n)) {
                        if (!o) {
                            i = n + 1;
                            break
                        }
                    } else -1 === s && (o = !1, s = n + 1);
                    return -1 === s ? "" : e.slice(i, s)
                }, extname: function (e) {
                    t(e);
                    for (var r = -1, n = 0, i = -1, s = !0, o = 0, a = e.length - 1; a >= 0; --a) {
                        var h = e.charCodeAt(a);
                        if (47 !== h) -1 === i && (s = !1, i = a + 1), 46 === h ? -1 === r ? r = a : 1 !== o && (o = 1) : -1 !== r && (o = -1); else if (!s) {
                            n = a + 1;
                            break
                        }
                    }
                    return -1 === r || -1 === i || 0 === o || 1 === o && r === i - 1 && r === n + 1 ? "" : e.slice(r, i)
                }, format: function (e) {
                    if (null === e || "object" != typeof e) throw new TypeError('The "pathObject" argument must be of type Object. Received type ' + typeof e);
                    return function (e, t) {
                        var r = t.dir || t.root, n = t.base || (t.name || "") + (t.ext || "");
                        return r ? r === t.root ? r + n : r + "/" + n : n
                    }(0, e)
                }, parse: function (e) {
                    t(e);
                    var r = {root: "", dir: "", base: "", ext: "", name: ""};
                    if (0 === e.length) return r;
                    var n, i = e.charCodeAt(0), s = 47 === i;
                    s ? (r.root = "/", n = 1) : n = 0;
                    for (var o = -1, a = 0, h = -1, u = !0, c = e.length - 1, d = 0; c >= n; --c) if (47 !== (i = e.charCodeAt(c))) -1 === h && (u = !1, h = c + 1), 46 === i ? -1 === o ? o = c : 1 !== d && (d = 1) : -1 !== o && (d = -1); else if (!u) {
                        a = c + 1;
                        break
                    }
                    return -1 === o || -1 === h || 0 === d || 1 === d && o === h - 1 && o === a + 1 ? -1 !== h && (r.base = r.name = 0 === a && s ? e.slice(1, h) : e.slice(a, h)) : (0 === a && s ? (r.name = e.slice(1, o), r.base = e.slice(1, h)) : (r.name = e.slice(a, o), r.base = e.slice(a, h)), r.ext = e.slice(o, h)), a > 0 ? r.dir = e.slice(0, a - 1) : s && (r.dir = "/"), r
                }, sep: "/", delimiter: ":", win32: null, posix: null
            };
            n.posix = n, ft = n
        }).call(this)
    }).call(this, _e);
    var pt;
    pt = _t, _t.strict = yt, _t.loose = bt;
    var mt = Object.prototype.toString, gt = {
        "[object Int8Array]": !0,
        "[object Int16Array]": !0,
        "[object Int32Array]": !0,
        "[object Uint8Array]": !0,
        "[object Uint8ClampedArray]": !0,
        "[object Uint16Array]": !0,
        "[object Uint32Array]": !0,
        "[object Float32Array]": !0,
        "[object Float64Array]": !0
    };

    function _t(e) {
        return yt(e) || bt(e)
    }

    function yt(e) {
        return e instanceof Int8Array || e instanceof Int16Array || e instanceof Int32Array || e instanceof Uint8Array || e instanceof Uint8ClampedArray || e instanceof Uint16Array || e instanceof Uint32Array || e instanceof Float32Array || e instanceof Float64Array
    }

    function bt(e) {
        return gt[mt.call(e)]
    }

    var vt = {};
    (function (e) {
        (function () {
            var t = pt.strict;
            vt = function (r) {
                if (t(r)) {
                    var n = e.from(r.buffer);
                    return r.byteLength !== r.buffer.byteLength && (n = n.slice(r.byteOffset, r.byteOffset + r.byteLength)), n
                }
                return e.from(r)
            }
        }).call(this)
    }).call(this, y({}).Buffer);
    const {Readable: wt} = dt;
    var Et, kt = class extends wt {
        constructor(e, t = {}) {
            super(t), this._offset = 0, this._ready = !1, this._file = e, this._size = e.size, this._chunkSize = t.chunkSize || Math.max(this._size / 1e3, 204800);
            const r = new FileReader;
            r.onload = () => {
                this.push(vt(r.result))
            }, r.onerror = () => {
                this.emit("error", r.error)
            }, this.reader = r, this._generateHeaderBlocks(e, t, (e, t) => {
                if (e) return this.emit("error", e);
                Array.isArray(t) && t.forEach(e => this.push(e)), this._ready = !0, this.emit("_ready")
            })
        }

        _generateHeaderBlocks(e, t, r) {
            r(null, [])
        }

        _read() {
            if (!this._ready) return void this.once("_ready", this._read.bind(this));
            const e = this._offset;
            let t = this._offset + this._chunkSize;
            if (t > this._size && (t = this._size), e === this._size) return this.destroy(), void this.push(null);
            this.reader.readAsArrayBuffer(this._file.slice(e, t)), this._offset = t
        }

        destroy() {
            if (this._file = null, this.reader) {
                this.reader.onload = null, this.reader.onerror = null;
                try {
                    this.reader.abort()
                } catch (e) {
                }
            }
            this.reader = null
        }
    }, St = {};

    function Ct(e) {
        return St.existsSync(e) && St.statSync(e).isFile()
    }

    (Et = function (e, t) {
        if (!t) return Ct(e);
        St.stat(e, (function (e, r) {
            return e ? t(e) : t(null, r.isFile())
        }))
    }).sync = Ct;
    var xt = {};
    xt.regex = new RegExp(["^npm-debug\\.log$", "^\\..*\\.swp$", "^\\.DS_Store$", "^\\.AppleDouble$", "^\\.LSOverride$", "^Icon\\r$", "^\\._.*", "^\\.Spotlight-V100(?:$|\\/)", "\\.Trashes", "^__MACOSX$", "~$", "^Thumbs\\.db$", "^ehthumbs\\.db$", "^Desktop\\.ini$", "@eaDir$"].join("|")), xt.is = e => xt.regex.test(e), xt.not = e => !xt.is(e);
    var At = {};

    function Tt(e) {
        return Rt(e, {objectMode: !0, highWaterMark: 16})
    }

    function It(e) {
        return Rt(e)
    }

    function Rt(e, t) {
        if (!e || "function" == typeof e || e._readableState) return e;
        var r = new dt.Readable(t).wrap(e);
        return e.destroy && (r.destroy = e.destroy.bind(e)), r
    }

    class Bt extends dt.Readable {
        constructor(e, t) {
            super(t), this.destroyed = !1, this._drained = !1, this._forwarding = !1, this._current = null, this._toStreams2 = t && t.objectMode ? Tt : It, "function" == typeof e ? this._queue = e : (this._queue = e.map(this._toStreams2), this._queue.forEach(e => {
                "function" != typeof e && this._attachErrorListener(e)
            })), this._next()
        }

        _read() {
            this._drained = !0, this._forward()
        }

        _forward() {
            if (!this._forwarding && this._drained && this._current) {
                var e;
                for (this._forwarding = !0; this._drained && null !== (e = this._current.read());) this._drained = this.push(e);
                this._forwarding = !1
            }
        }

        destroy(e) {
            this.destroyed || (this.destroyed = !0, this._current && this._current.destroy && this._current.destroy(), "function" != typeof this._queue && this._queue.forEach(e => {
                e.destroy && e.destroy()
            }), e && this.emit("error", e), this.emit("close"))
        }

        _next() {
            if (this._current = null, "function" == typeof this._queue) this._queue((e, t) => {
                if (e) return this.destroy(e);
                t = this._toStreams2(t), this._attachErrorListener(t), this._gotNextStream(t)
            }); else {
                var e = this._queue.shift();
                "function" == typeof e && (e = this._toStreams2(e()), this._attachErrorListener(e)), this._gotNextStream(e)
            }
        }

        _gotNextStream(e) {
            if (!e) return this.push(null), void this.destroy();
            this._current = e, this._forward();
            const t = () => {
                this._forward()
            }, r = () => {
                e._readableState.ended || this.destroy()
            }, n = () => {
                this._current = null, e.removeListener("readable", t), e.removeListener("end", n), e.removeListener("close", r), this._next()
            };
            e.on("readable", t), e.once("end", n), e.once("close", r)
        }

        _attachErrorListener(e) {
            if (!e) return;
            const t = r => {
                e.removeListener("error", t), this.destroy(r)
            };
            e.once("error", t)
        }
    }

    Bt.obj = e => new Bt(e, {objectMode: !0, highWaterMark: 16}), At = Bt;
    var Lt = function e(t, r) {
        if (t && r) return e(t)(r);
        if ("function" != typeof t) throw new TypeError("need wrapper function");
        return Object.keys(t).forEach((function (e) {
            n[e] = t[e]
        })), n;

        function n() {
            for (var e = new Array(arguments.length), r = 0; r < e.length; r++) e[r] = arguments[r];
            var n = t.apply(this, e), i = e[e.length - 1];
            return "function" == typeof n && n !== i && Object.keys(i).forEach((function (e) {
                n[e] = i[e]
            })), n
        }
    }, Ot = {};

    function Ut(e) {
        var t = function () {
            return t.called ? t.value : (t.called = !0, t.value = e.apply(this, arguments))
        };
        return t.called = !1, t
    }

    function Pt(e) {
        var t = function () {
            if (t.called) throw new Error(t.onceError);
            return t.called = !0, t.value = e.apply(this, arguments)
        }, r = e.name || "Function wrapped with `once`";
        return t.onceError = r + " shouldn't be called more than once", t.called = !1, t
    }

    (Ot = Lt(Ut)).strict = Lt(Pt), Ut.proto = Ut((function () {
        Object.defineProperty(Function.prototype, "once", {
            value: function () {
                return Ut(this)
            }, configurable: !0
        }), Object.defineProperty(Function.prototype, "onceStrict", {
            value: function () {
                return Pt(this)
            }, configurable: !0
        })
    }));
    var Mt = {};
    (function (e) {
        (function () {
            Mt = function (t, r) {
                var n, i, s, o = !0;

                function a(t) {
                    function i() {
                        r && r(t, n), r = null
                    }

                    o ? e.nextTick(i) : i()
                }

                function h(e, t, r) {
                    n[e] = r, (0 == --i || t) && a(t)
                }

                Array.isArray(t) ? (n = [], i = t.length) : (s = Object.keys(t), n = {}, i = s.length), i ? s ? s.forEach((function (e) {
                    t[e]((function (t, r) {
                        h(e, t, r)
                    }))
                })) : t.forEach((function (e, t) {
                    e((function (e, r) {
                        h(t, e, r)
                    }))
                })) : a(null), o = !1
            }
        }).call(this)
    }).call(this, _e);
    var Dt, Nt, jt, Ft, zt, Ht = {exports: {}};
    Dt = "undefined" != typeof self ? self : this, Nt = function () {
        return function (e) {
            var t = {};

            function r(n) {
                if (t[n]) return t[n].exports;
                var i = t[n] = {i: n, l: !1, exports: {}};
                return e[n].call(i.exports, i, i.exports, r), i.l = !0, i.exports
            }

            return r.m = e, r.c = t, r.d = function (e, t, n) {
                r.o(e, t) || Object.defineProperty(e, t, {configurable: !1, enumerable: !0, get: n})
            }, r.n = function (e) {
                var t = e && e.__esModule ? function () {
                    return e.default
                } : function () {
                    return e
                };
                return r.d(t, "a", t), t
            }, r.o = function (e, t) {
                return Object.prototype.hasOwnProperty.call(e, t)
            }, r.p = "", r(r.s = 3)
        }([function (e, t, r) {
            var n = r(5), i = r(1), s = i.toHex, o = i.ceilHeapSize, a = r(6), h = function (e) {
                for (e += 9; e % 64 > 0; e += 1) ;
                return e
            }, u = function (e, t) {
                var r = new Int32Array(e, t + 320, 5), n = new Int32Array(5), i = new DataView(n.buffer);
                return i.setInt32(0, r[0], !1), i.setInt32(4, r[1], !1), i.setInt32(8, r[2], !1), i.setInt32(12, r[3], !1), i.setInt32(16, r[4], !1), n
            }, c = function () {
                function e(t) {
                    if (function (e, t) {
                        if (!(e instanceof t)) throw new TypeError("Cannot call a class as a function")
                    }(this, e), (t = t || 65536) % 64 > 0) throw new Error("Chunk size must be a multiple of 128 bit");
                    this._offset = 0, this._maxChunkLen = t, this._padMaxChunkLen = h(t), this._heap = new ArrayBuffer(o(this._padMaxChunkLen + 320 + 20)), this._h32 = new Int32Array(this._heap), this._h8 = new Int8Array(this._heap), this._core = new n({Int32Array: Int32Array}, {}, this._heap)
                }

                return e.prototype._initState = function (e, t) {
                    this._offset = 0;
                    var r = new Int32Array(e, t + 320, 5);
                    r[0] = 1732584193, r[1] = -271733879, r[2] = -1732584194, r[3] = 271733878, r[4] = -1009589776
                }, e.prototype._padChunk = function (e, t) {
                    var r = h(e), n = new Int32Array(this._heap, 0, r >> 2);
                    return function (e, t) {
                        var r = new Uint8Array(e.buffer), n = t % 4, i = t - n;
                        switch (n) {
                            case 0:
                                r[i + 3] = 0;
                            case 1:
                                r[i + 2] = 0;
                            case 2:
                                r[i + 1] = 0;
                            case 3:
                                r[i + 0] = 0
                        }
                        for (var s = 1 + (t >> 2); s < e.length; s++) e[s] = 0
                    }(n, e), function (e, t, r) {
                        e[t >> 2] |= 128 << 24 - (t % 4 << 3), e[14 + (2 + (t >> 2) & -16)] = r / (1 << 29) | 0, e[15 + (2 + (t >> 2) & -16)] = r << 3
                    }(n, e, t), r
                }, e.prototype._write = function (e, t, r, n) {
                    a(e, this._h8, this._h32, t, r, n || 0)
                }, e.prototype._coreCall = function (e, t, r, n, i) {
                    var s = r;
                    this._write(e, t, r), i && (s = this._padChunk(r, n)), this._core.hash(s, this._padMaxChunkLen)
                }, e.prototype.rawDigest = function (e) {
                    var t = e.byteLength || e.length || e.size || 0;
                    this._initState(this._heap, this._padMaxChunkLen);
                    var r = 0, n = this._maxChunkLen;
                    for (r = 0; t > r + n; r += n) this._coreCall(e, r, n, t, !1);
                    return this._coreCall(e, r, t - r, t, !0), u(this._heap, this._padMaxChunkLen)
                }, e.prototype.digest = function (e) {
                    return s(this.rawDigest(e).buffer)
                }, e.prototype.digestFromString = function (e) {
                    return this.digest(e)
                }, e.prototype.digestFromBuffer = function (e) {
                    return this.digest(e)
                }, e.prototype.digestFromArrayBuffer = function (e) {
                    return this.digest(e)
                }, e.prototype.resetState = function () {
                    return this._initState(this._heap, this._padMaxChunkLen), this
                }, e.prototype.append = function (e) {
                    var t = 0, r = e.byteLength || e.length || e.size || 0, n = this._offset % this._maxChunkLen,
                        i = void 0;
                    for (this._offset += r; t < r;) i = Math.min(r - t, this._maxChunkLen - n), this._write(e, t, i, n), t += i, (n += i) === this._maxChunkLen && (this._core.hash(this._maxChunkLen, this._padMaxChunkLen), n = 0);
                    return this
                }, e.prototype.getState = function () {
                    var e = void 0;
                    if (this._offset % this._maxChunkLen) e = this._heap.slice(0); else {
                        var t = new Int32Array(this._heap, this._padMaxChunkLen + 320, 5);
                        e = t.buffer.slice(t.byteOffset, t.byteOffset + t.byteLength)
                    }
                    return {offset: this._offset, heap: e}
                }, e.prototype.setState = function (e) {
                    return this._offset = e.offset, 20 === e.heap.byteLength ? new Int32Array(this._heap, this._padMaxChunkLen + 320, 5).set(new Int32Array(e.heap)) : this._h32.set(new Int32Array(e.heap)), this
                }, e.prototype.rawEnd = function () {
                    var e = this._offset, t = e % this._maxChunkLen, r = this._padChunk(t, e);
                    this._core.hash(r, this._padMaxChunkLen);
                    var n = u(this._heap, this._padMaxChunkLen);
                    return this._initState(this._heap, this._padMaxChunkLen), n
                }, e.prototype.end = function () {
                    return s(this.rawEnd().buffer)
                }, e
            }();
            e.exports = c, e.exports._core = n
        }, function (e, t) {
            for (var r = new Array(256), n = 0; n < 256; n++) r[n] = (n < 16 ? "0" : "") + n.toString(16);
            e.exports.toHex = function (e) {
                for (var t = new Uint8Array(e), n = new Array(e.byteLength), i = 0; i < n.length; i++) n[i] = r[t[i]];
                return n.join("")
            }, e.exports.ceilHeapSize = function (e) {
                var t = 0;
                if (e <= 65536) return 65536;
                if (e < 16777216) for (t = 1; t < e; t <<= 1) ; else for (t = 16777216; t < e; t += 16777216) ;
                return t
            }, e.exports.isDedicatedWorkerScope = function (e) {
                var t = "WorkerGlobalScope" in e && e instanceof e.WorkerGlobalScope,
                    r = "SharedWorkerGlobalScope" in e && e instanceof e.SharedWorkerGlobalScope,
                    n = "ServiceWorkerGlobalScope" in e && e instanceof e.ServiceWorkerGlobalScope;
                return t && !r && !n
            }
        }, function (e, t, r) {
            e.exports = function () {
                var e = r(0), t = function (e, r, n, i, s) {
                    var o = new self.FileReader;
                    o.onloadend = function () {
                        if (o.error) return s(o.error);
                        var a = o.result;
                        r += o.result.byteLength;
                        try {
                            e.append(a)
                        } catch (h) {
                            return void s(h)
                        }
                        r < i.size ? t(e, r, n, i, s) : s(null, e.end())
                    }, o.readAsArrayBuffer(i.slice(r, r + n))
                }, n = !0;
                return self.onmessage = function (r) {
                    if (n) {
                        var i = r.data.data, s = r.data.file, o = r.data.id;
                        if (void 0 !== o && (s || i)) {
                            var a = r.data.blockSize || 4194304, h = new e(a);
                            h.resetState();
                            var u = function (e, t) {
                                e ? self.postMessage({id: o, error: e.name}) : self.postMessage({id: o, hash: t})
                            };
                            i && function (e, t, r) {
                                try {
                                    r(null, e.digest(t))
                                } catch (n) {
                                    return r(n)
                                }
                            }(h, i, u), s && t(h, 0, a, s, u)
                        }
                    }
                }, function () {
                    n = !1
                }
            }
        }, function (e, t, r) {
            var n = r(4), i = r(0), s = r(7), o = r(2), a = r(1).isDedicatedWorkerScope,
                h = "undefined" != typeof self && a(self);
            i.disableWorkerBehaviour = h ? o() : function () {
            }, i.createWorker = function () {
                var e = n(2), t = e.terminate;
                return e.terminate = function () {
                    URL.revokeObjectURL(e.objectURL), t.call(e)
                }, e
            }, i.createHash = s, e.exports = i
        }, function (e, t, r) {
            function n(e) {
                var t = {};

                function r(n) {
                    if (t[n]) return t[n].exports;
                    var i = t[n] = {i: n, l: !1, exports: {}};
                    return e[n].call(i.exports, i, i.exports, r), i.l = !0, i.exports
                }

                r.m = e, r.c = t, r.i = function (e) {
                    return e
                }, r.d = function (e, t, n) {
                    r.o(e, t) || Object.defineProperty(e, t, {configurable: !1, enumerable: !0, get: n})
                }, r.r = function (e) {
                    Object.defineProperty(e, "__esModule", {value: !0})
                }, r.n = function (e) {
                    var t = e && e.__esModule ? function () {
                        return e.default
                    } : function () {
                        return e
                    };
                    return r.d(t, "a", t), t
                }, r.o = function (e, t) {
                    return Object.prototype.hasOwnProperty.call(e, t)
                }, r.p = "/", r.oe = function (e) {
                    throw console.error(e), e
                };
                var n = r(r.s = ENTRY_MODULE);
                return n.default || n
            }

            function i(e) {
                return (e + "").replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&")
            }

            function s(e, t, n) {
                var s = {};
                s[n] = [];
                var o = t.toString(), a = o.match(/^function\s?\(\w+,\s*\w+,\s*(\w+)\)/);
                if (!a) return s;
                for (var h, u = a[1], c = new RegExp("(\\\\n|\\W)" + i(u) + "\\((/\\*.*?\\*/)?s?.*?([\\.|\\-|\\+|\\w|/|@]+).*?\\)", "g"); h = c.exec(o);) "dll-reference" !== h[3] && s[n].push(h[3]);
                for (c = new RegExp("\\(" + i(u) + '\\("(dll-reference\\s([\\.|\\-|\\+|\\w|/|@]+))"\\)\\)\\((/\\*.*?\\*/)?s?.*?([\\.|\\-|\\+|\\w|/|@]+).*?\\)', "g"); h = c.exec(o);) e[h[2]] || (s[n].push(h[1]), e[h[2]] = r(h[1]).m), s[h[2]] = s[h[2]] || [], s[h[2]].push(h[4]);
                return s
            }

            function o(e) {
                return Object.keys(e).reduce((function (t, r) {
                    return t || e[r].length > 0
                }), !1)
            }

            e.exports = function (e, t) {
                t = t || {};
                var i = {main: r.m}, a = t.all ? {main: Object.keys(i)} : function (e, t) {
                    for (var r = {main: [t]}, n = {main: []}, i = {main: {}}; o(r);) for (var a = Object.keys(r), h = 0; h < a.length; h++) {
                        var u = a[h], c = r[u].pop();
                        if (i[u] = i[u] || {}, !i[u][c] && e[u][c]) {
                            i[u][c] = !0, n[u] = n[u] || [], n[u].push(c);
                            for (var d = s(e, e[u][c], u), l = Object.keys(d), f = 0; f < l.length; f++) r[l[f]] = r[l[f]] || [], r[l[f]] = r[l[f]].concat(d[l[f]])
                        }
                    }
                    return n
                }(i, e), h = "";
                Object.keys(a).filter((function (e) {
                    return "main" !== e
                })).forEach((function (e) {
                    for (var t = 0; a[e][t];) t++;
                    a[e].push(t), i[e][t] = "(function(module, exports, __webpack_require__) { module.exports = __webpack_require__; })", h = h + "var " + e + " = (" + n.toString().replace("ENTRY_MODULE", JSON.stringify(t)) + ")({" + a[e].map((function (t) {
                        return JSON.stringify(t) + ": " + i[e][t].toString()
                    })).join(",") + "});\n"
                })), h = h + "(" + n.toString().replace("ENTRY_MODULE", JSON.stringify(e)) + ")({" + a.main.map((function (e) {
                    return JSON.stringify(e) + ": " + i.main[e].toString()
                })).join(",") + "})(self);";
                var u = new window.Blob([h], {type: "text/javascript"});
                if (t.bare) return u;
                var c = (window.URL || window.webkitURL || window.mozURL || window.msURL).createObjectURL(u),
                    d = new window.Worker(c);
                return d.objectURL = c, d
            }
        }, function (e, t) {
            e.exports = function (e, t, r) {
                "use asm";
                var n = new e.Int32Array(r);

                function i(e, t) {
                    e = e | 0;
                    t = t | 0;
                    var r = 0, i = 0, s = 0, o = 0, a = 0, h = 0, u = 0, c = 0, d = 0, l = 0, f = 0, p = 0, m = 0,
                        g = 0;
                    s = n[t + 320 >> 2] | 0;
                    a = n[t + 324 >> 2] | 0;
                    u = n[t + 328 >> 2] | 0;
                    d = n[t + 332 >> 2] | 0;
                    f = n[t + 336 >> 2] | 0;
                    for (r = 0; (r | 0) < (e | 0); r = r + 64 | 0) {
                        o = s;
                        h = a;
                        c = u;
                        l = d;
                        p = f;
                        for (i = 0; (i | 0) < 64; i = i + 4 | 0) {
                            g = n[r + i >> 2] | 0;
                            m = ((s << 5 | s >>> 27) + (a & u | ~a & d) | 0) + ((g + f | 0) + 1518500249 | 0) | 0;
                            f = d;
                            d = u;
                            u = a << 30 | a >>> 2;
                            a = s;
                            s = m;
                            n[e + i >> 2] = g
                        }
                        for (i = e + 64 | 0; (i | 0) < (e + 80 | 0); i = i + 4 | 0) {
                            g = (n[i - 12 >> 2] ^ n[i - 32 >> 2] ^ n[i - 56 >> 2] ^ n[i - 64 >> 2]) << 1 | (n[i - 12 >> 2] ^ n[i - 32 >> 2] ^ n[i - 56 >> 2] ^ n[i - 64 >> 2]) >>> 31;
                            m = ((s << 5 | s >>> 27) + (a & u | ~a & d) | 0) + ((g + f | 0) + 1518500249 | 0) | 0;
                            f = d;
                            d = u;
                            u = a << 30 | a >>> 2;
                            a = s;
                            s = m;
                            n[i >> 2] = g
                        }
                        for (i = e + 80 | 0; (i | 0) < (e + 160 | 0); i = i + 4 | 0) {
                            g = (n[i - 12 >> 2] ^ n[i - 32 >> 2] ^ n[i - 56 >> 2] ^ n[i - 64 >> 2]) << 1 | (n[i - 12 >> 2] ^ n[i - 32 >> 2] ^ n[i - 56 >> 2] ^ n[i - 64 >> 2]) >>> 31;
                            m = ((s << 5 | s >>> 27) + (a ^ u ^ d) | 0) + ((g + f | 0) + 1859775393 | 0) | 0;
                            f = d;
                            d = u;
                            u = a << 30 | a >>> 2;
                            a = s;
                            s = m;
                            n[i >> 2] = g
                        }
                        for (i = e + 160 | 0; (i | 0) < (e + 240 | 0); i = i + 4 | 0) {
                            g = (n[i - 12 >> 2] ^ n[i - 32 >> 2] ^ n[i - 56 >> 2] ^ n[i - 64 >> 2]) << 1 | (n[i - 12 >> 2] ^ n[i - 32 >> 2] ^ n[i - 56 >> 2] ^ n[i - 64 >> 2]) >>> 31;
                            m = ((s << 5 | s >>> 27) + (a & u | a & d | u & d) | 0) + ((g + f | 0) - 1894007588 | 0) | 0;
                            f = d;
                            d = u;
                            u = a << 30 | a >>> 2;
                            a = s;
                            s = m;
                            n[i >> 2] = g
                        }
                        for (i = e + 240 | 0; (i | 0) < (e + 320 | 0); i = i + 4 | 0) {
                            g = (n[i - 12 >> 2] ^ n[i - 32 >> 2] ^ n[i - 56 >> 2] ^ n[i - 64 >> 2]) << 1 | (n[i - 12 >> 2] ^ n[i - 32 >> 2] ^ n[i - 56 >> 2] ^ n[i - 64 >> 2]) >>> 31;
                            m = ((s << 5 | s >>> 27) + (a ^ u ^ d) | 0) + ((g + f | 0) - 899497514 | 0) | 0;
                            f = d;
                            d = u;
                            u = a << 30 | a >>> 2;
                            a = s;
                            s = m;
                            n[i >> 2] = g
                        }
                        s = s + o | 0;
                        a = a + h | 0;
                        u = u + c | 0;
                        d = d + l | 0;
                        f = f + p | 0
                    }
                    n[t + 320 >> 2] = s;
                    n[t + 324 >> 2] = a;
                    n[t + 328 >> 2] = u;
                    n[t + 332 >> 2] = d;
                    n[t + 336 >> 2] = f
                }

                return {hash: i}
            }
        }, function (e, t) {
            var r = this, n = void 0;
            "undefined" != typeof self && void 0 !== self.FileReaderSync && (n = new self.FileReaderSync);
            var i = function (e, t, r, n, i, s) {
                var o = void 0, a = s % 4, h = (i + a) % 4, u = i - h;
                switch (a) {
                    case 0:
                        t[s] = e[n + 3];
                    case 1:
                        t[s + 1 - (a << 1) | 0] = e[n + 2];
                    case 2:
                        t[s + 2 - (a << 1) | 0] = e[n + 1];
                    case 3:
                        t[s + 3 - (a << 1) | 0] = e[n]
                }
                if (!(i < h + (4 - a))) {
                    for (o = 4 - a; o < u; o = o + 4 | 0) r[s + o >> 2 | 0] = e[n + o] << 24 | e[n + o + 1] << 16 | e[n + o + 2] << 8 | e[n + o + 3];
                    switch (h) {
                        case 3:
                            t[s + u + 1 | 0] = e[n + u + 2];
                        case 2:
                            t[s + u + 2 | 0] = e[n + u + 1];
                        case 1:
                            t[s + u + 3 | 0] = e[n + u]
                    }
                }
            };
            e.exports = function (e, t, s, o, a, h) {
                if ("string" == typeof e) return function (e, t, r, n, i, s) {
                    var o = void 0, a = s % 4, h = (i + a) % 4, u = i - h;
                    switch (a) {
                        case 0:
                            t[s] = e.charCodeAt(n + 3);
                        case 1:
                            t[s + 1 - (a << 1) | 0] = e.charCodeAt(n + 2);
                        case 2:
                            t[s + 2 - (a << 1) | 0] = e.charCodeAt(n + 1);
                        case 3:
                            t[s + 3 - (a << 1) | 0] = e.charCodeAt(n)
                    }
                    if (!(i < h + (4 - a))) {
                        for (o = 4 - a; o < u; o = o + 4 | 0) r[s + o >> 2] = e.charCodeAt(n + o) << 24 | e.charCodeAt(n + o + 1) << 16 | e.charCodeAt(n + o + 2) << 8 | e.charCodeAt(n + o + 3);
                        switch (h) {
                            case 3:
                                t[s + u + 1 | 0] = e.charCodeAt(n + u + 2);
                            case 2:
                                t[s + u + 2 | 0] = e.charCodeAt(n + u + 1);
                            case 1:
                                t[s + u + 3 | 0] = e.charCodeAt(n + u)
                        }
                    }
                }(e, t, s, o, a, h);
                if (e instanceof Array) return i(e, t, s, o, a, h);
                if (r && r.Buffer && r.Buffer.isBuffer(e)) return i(e, t, s, o, a, h);
                if (e instanceof ArrayBuffer) return i(new Uint8Array(e), t, s, o, a, h);
                if (e.buffer instanceof ArrayBuffer) return i(new Uint8Array(e.buffer, e.byteOffset, e.byteLength), t, s, o, a, h);
                if (e instanceof Blob) return function (e, t, r, i, s, o) {
                    var a = void 0, h = o % 4, u = (s + h) % 4, c = s - u,
                        d = new Uint8Array(n.readAsArrayBuffer(e.slice(i, i + s)));
                    switch (h) {
                        case 0:
                            t[o] = d[3];
                        case 1:
                            t[o + 1 - (h << 1) | 0] = d[2];
                        case 2:
                            t[o + 2 - (h << 1) | 0] = d[1];
                        case 3:
                            t[o + 3 - (h << 1) | 0] = d[0]
                    }
                    if (!(s < u + (4 - h))) {
                        for (a = 4 - h; a < c; a = a + 4 | 0) r[o + a >> 2 | 0] = d[a] << 24 | d[a + 1] << 16 | d[a + 2] << 8 | d[a + 3];
                        switch (u) {
                            case 3:
                                t[o + c + 1 | 0] = d[c + 2];
                            case 2:
                                t[o + c + 2 | 0] = d[c + 1];
                            case 1:
                                t[o + c + 3 | 0] = d[c]
                        }
                    }
                }(e, t, s, o, a, h);
                throw new Error("Unsupported data type.")
            }
        }, function (e, t, r) {
            var n = r(0), i = r(1).toHex, s = function () {
                function e() {
                    !function (e, t) {
                        if (!(e instanceof t)) throw new TypeError("Cannot call a class as a function")
                    }(this, e), this._rusha = new n, this._rusha.resetState()
                }

                return e.prototype.update = function (e) {
                    return this._rusha.append(e), this
                }, e.prototype.digest = function (e) {
                    var t = this._rusha.rawEnd().buffer;
                    if (!e) return t;
                    if ("hex" === e) return i(t);
                    throw new Error("unsupported digest encoding")
                }, e
            }();
            e.exports = function () {
                return new s
            }
        }])
    }, "object" == typeof Ht.exports ? Ht.exports = Nt() : "function" == typeof define && define.amd ? define([], Nt) : "object" == typeof Ht.exports ? Ht.exports.Rusha = Nt() : Dt.Rusha = Nt(), Ht = Ht.exports;
    var Wt, qt = new Ht, Zt = "undefined" != typeof window ? window : self, Vt = Zt.crypto || Zt.msCrypto || {},
        $t = Vt.subtle || Vt.webkitSubtle;

    function Kt(e) {
        return qt.digest(e)
    }

    try {
        $t.digest({name: "sha-1"}, new Uint8Array).catch((function () {
            $t = !1
        }))
    } catch (Ga) {
        $t = !1
    }
    (Wt = function (e, t) {
        $t ? ("string" == typeof e && (e = function (e) {
            for (var t = e.length, r = new Uint8Array(t), n = 0; n < t; n++) r[n] = e.charCodeAt(n);
            return r
        }(e)), $t.digest({name: "sha-1"}, e).then((function (e) {
            t(function (e) {
                for (var t = e.length, r = [], n = 0; n < t; n++) {
                    var i = e[n];
                    r.push((i >>> 4).toString(16)), r.push((15 & i).toString(16))
                }
                return r.join("")
            }(new Uint8Array(e)))
        }), (function () {
            t(Kt(e))
        }))) : "undefined" != typeof window ? function (e, t) {
            jt || (jt = Ht.createWorker(), Ft = 1, zt = {}, jt.onmessage = function (e) {
                var t = e.data.id, r = zt[t];
                delete zt[t], null != e.data.error ? r(new Error("Rusha worker error: " + e.data.error)) : r(null, e.data.hash)
            }), zt[Ft] = t, jt.postMessage({id: Ft, data: e}), Ft += 1
        }(e, (function (r, n) {
            t(r ? Kt(e) : n)
        })) : queueMicrotask(() => t(Kt(e)))
    }).sync = Kt;
    var Gt = {};
    (function (e, t, r) {
        (function () {
            function n(t, n, i) {
                if ("undefined" != typeof FileList && t instanceof FileList && (t = Array.from(t)), Array.isArray(t) || (t = [t]), 0 === t.length) throw new Error("invalid input type");
                t.forEach(e => {
                    if (null == e) throw new Error("invalid input type: " + e)
                }), 1 !== (t = t.map(e => s(e) && "string" == typeof e.path && "function" == typeof ae ? e.path : e)).length || "string" == typeof t[0] || t[0].name || (t[0].name = n.name);
                let a = null;
                t.forEach((e, r) => {
                    if ("string" == typeof e) return;
                    let n = e.fullPath || e.name;
                    n || (n = "Unknown File " + (r + 1), e.unknownName = !0), e.path = n.split("/"), e.path[0] || e.path.shift(), e.path.length < 2 ? a = null : 0 === r && t.length > 1 ? a = e.path[0] : e.path[0] !== a && (a = null)
                }), t = t.filter(e => {
                    if ("string" == typeof e) return !0;
                    const t = e.path[e.path.length - 1];
                    return "." !== t[0] && xt.not(t)
                }), a && t.forEach(e => {
                    const t = (r.isBuffer(e) || o(e)) && !e.path;
                    "string" == typeof e || t || e.path.shift()
                }), !n.name && a && (n.name = a), n.name || t.some(e => "string" == typeof e ? (n.name = ft.basename(e), !0) : e.unknownName ? void 0 : (n.name = e.path[e.path.length - 1], !0)), n.name || (n.name = "Unnamed Torrent " + Date.now());
                const h = t.reduce((e, t) => e + Number("string" == typeof t), 0);
                let u = 1 === t.length;
                if (1 === t.length && "string" == typeof t[0]) {
                    if ("function" != typeof ae) throw new Error("filesystem paths do not work in the browser");
                    Et(t[0], (e, t) => {
                        if (e) return i(e);
                        u = t, c()
                    })
                } else e.nextTick(() => {
                    c()
                });

                function c() {
                    Mt(t.map(e => t => {
                        const n = {};
                        if (s(e)) n.getStream = function (e) {
                            return () => new kt(e)
                        }(e), n.length = e.size; else if (r.isBuffer(e)) n.getStream = function (e) {
                            return () => {
                                const t = new dt.PassThrough;
                                return t.end(e), t
                            }
                        }(e), n.length = e.length; else {
                            if (!o(e)) {
                                if ("string" == typeof e) {
                                    if ("function" != typeof ae) throw new Error("filesystem paths do not work in the browser");
                                    return void ae(e, h > 1 || u, t)
                                }
                                throw new Error("invalid input type")
                            }
                            n.getStream = function (e, t) {
                                return () => {
                                    const r = new dt.Transform;
                                    return r._transform = function (e, r, n) {
                                        t.length += e.length, this.push(e), n()
                                    }, e.pipe(r), r
                                }
                            }(e, n), n.length = 0
                        }
                        n.path = e.path, t(null, n)
                    }), (e, t) => {
                        if (e) return i(e);
                        t = function e(t) {
                            return t.reduce((t, r) => Array.isArray(r) ? t.concat(e(r)) : t.concat(r), [])
                        }(t), i(null, t, u)
                    })
                }
            }

            function i(e, t) {
                return e + t.length
            }

            function s(e) {
                return "undefined" != typeof Blob && e instanceof Blob
            }

            function o(e) {
                return "object" == typeof e && null != e && "function" == typeof e.pipe
            }

            (Gt = function (e, s, o) {
                "function" == typeof s && ([s, o] = [o, s]), n(e, s = s ? Object.assign({}, s) : {}, (e, n, a) => {
                    if (e) return o(e);
                    s.singleFileTorrent = a, function (e, n, s) {
                        let o = n.announceList;
                        o || ("string" == typeof n.announce ? o = [[n.announce]] : Array.isArray(n.announce) && (o = n.announce.map(e => [e]))), o || (o = []), t.WEBTORRENT_ANNOUNCE && ("string" == typeof t.WEBTORRENT_ANNOUNCE ? o.push([[t.WEBTORRENT_ANNOUNCE]]) : Array.isArray(t.WEBTORRENT_ANNOUNCE) && (o = o.concat(t.WEBTORRENT_ANNOUNCE.map(e => [e])))), void 0 === n.announce && void 0 === n.announceList && (o = o.concat(Gt.announceList)), "string" == typeof n.urlList && (n.urlList = [n.urlList]);
                        const a = {
                            info: {name: n.name},
                            "creation date": Math.ceil((Number(n.creationDate) || Date.now()) / 1e3),
                            encoding: "UTF-8"
                        };
                        0 !== o.length && (a.announce = o[0][0], a["announce-list"] = o), void 0 !== n.comment && (a.comment = n.comment), void 0 !== n.createdBy && (a["created by"] = n.createdBy), void 0 !== n.private && (a.info.private = Number(n.private)), void 0 !== n.info && Object.assign(a.info, n.info), void 0 !== n.sslCert && (a.info["ssl-cert"] = n.sslCert), void 0 !== n.urlList && (a["url-list"] = n.urlList);
                        const h = n.pieceLength || (u = e.reduce(i, 0), Math.max(16384, 1 << Math.log2(u < 1024 ? 1 : u / 1024) + .5 | 0));
                        var u;
                        a.info["piece length"] = h, function (e, t, n) {
                            n = Ot(n);
                            const i = [];
                            let s = 0;
                            const o = e.map(e => e.getStream);
                            let a = 0, h = 0, u = !1;
                            const c = new At(o), d = new lt(t, {zeroPadding: !1});

                            function l(e) {
                                s += e.length;
                                const t = h;
                                Wt(e, e => {
                                    i[t] = e, a -= 1, g()
                                }), a += 1, h += 1
                            }

                            function f() {
                                u = !0, g()
                            }

                            function p(e) {
                                m(), n(e)
                            }

                            function m() {
                                c.removeListener("error", p), d.removeListener("data", l), d.removeListener("end", f), d.removeListener("error", p)
                            }

                            function g() {
                                u && 0 === a && (m(), n(null, r.from(i.join(""), "hex"), s))
                            }

                            c.on("error", p), c.pipe(d).on("data", l).on("end", f).on("error", p)
                        }(e, h, (t, r, i) => {
                            if (t) return s(t);
                            a.info.pieces = r, e.forEach(e => {
                                delete e.getStream
                            }), n.singleFileTorrent ? a.info.length = i : a.info.files = e, s(null, q.encode(a))
                        })
                    }(n, s, o)
                })
            }).parseInput = function (e, t, r) {
                "function" == typeof t && ([t, r] = [r, t]), n(e, t = t ? Object.assign({}, t) : {}, r)
            }, Gt.announceList = [["udp://tracker.leechers-paradise.org:6969"], ["udp://tracker.coppersurfer.tk:6969"], ["udp://tracker.opentrackr.org:1337"], ["udp://explodie.org:6969"], ["udp://tracker.empire-js.us:1337"], ["wss://tracker.btorrent.xyz"], ["wss://tracker.openwebtorrent.com"]]
        }).call(this)
    }).call(this, _e, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {}, y({}).Buffer);
    var Xt = {};
    (function (e) {
        (function () {
            Xt.formatArgs = function (e) {
                if (e[0] = (this.useColors ? "%c" : "") + this.namespace + (this.useColors ? " %c" : " ") + e[0] + (this.useColors ? "%c " : " ") + "+" + Xt.humanize(this.diff), !this.useColors) return;
                const t = "color: " + this.color;
                e.splice(1, 0, t, "color: inherit");
                let r = 0, n = 0;
                e[0].replace(/%[a-zA-Z%]/g, e => {
                    "%%" !== e && (r++, "%c" === e && (n = r))
                }), e.splice(n, 0, t)
            }, Xt.save = function (e) {
                try {
                    e ? Xt.storage.setItem("debug", e) : Xt.storage.removeItem("debug")
                } catch (t) {
                }
            }, Xt.load = function () {
                let t;
                try {
                    t = Xt.storage.getItem("debug")
                } catch (r) {
                }
                return !t && void 0 !== e && "env" in e && (t = e.env.DEBUG), t
            }, Xt.useColors = function () {
                return !("undefined" == typeof window || !window.process || "renderer" !== window.process.type && !window.process.__nwjs) || ("undefined" == typeof navigator || !navigator.userAgent || !navigator.userAgent.toLowerCase().match(/(edge|trident)\/(\d+)/)) && ("undefined" != typeof document && document.documentElement && document.documentElement.style && document.documentElement.style.WebkitAppearance || "undefined" != typeof window && window.console && (window.console.firebug || window.console.exception && window.console.table) || "undefined" != typeof navigator && navigator.userAgent && navigator.userAgent.toLowerCase().match(/firefox\/(\d+)/) && parseInt(RegExp.$1, 10) >= 31 || "undefined" != typeof navigator && navigator.userAgent && navigator.userAgent.toLowerCase().match(/applewebkit\/(\d+)/))
            }, Xt.storage = function () {
                try {
                    return localStorage
                } catch (e) {
                }
            }(), Xt.destroy = (() => {
                let e = !1;
                return () => {
                    e || (e = !0, console.warn("Instance method `debug.destroy()` is deprecated and no longer does anything. It will be removed in the next major version of `debug`."))
                }
            })(), Xt.colors = ["#0000CC", "#0000FF", "#0033CC", "#0033FF", "#0066CC", "#0066FF", "#0099CC", "#0099FF", "#00CC00", "#00CC33", "#00CC66", "#00CC99", "#00CCCC", "#00CCFF", "#3300CC", "#3300FF", "#3333CC", "#3333FF", "#3366CC", "#3366FF", "#3399CC", "#3399FF", "#33CC00", "#33CC33", "#33CC66", "#33CC99", "#33CCCC", "#33CCFF", "#6600CC", "#6600FF", "#6633CC", "#6633FF", "#66CC00", "#66CC33", "#9900CC", "#9900FF", "#9933CC", "#9933FF", "#99CC00", "#99CC33", "#CC0000", "#CC0033", "#CC0066", "#CC0099", "#CC00CC", "#CC00FF", "#CC3300", "#CC3333", "#CC3366", "#CC3399", "#CC33CC", "#CC33FF", "#CC6600", "#CC6633", "#CC9900", "#CC9933", "#CCCC00", "#CCCC33", "#FF0000", "#FF0033", "#FF0066", "#FF0099", "#FF00CC", "#FF00FF", "#FF3300", "#FF3333", "#FF3366", "#FF3399", "#FF33CC", "#FF33FF", "#FF6600", "#FF6633", "#FF9900", "#FF9933", "#FFCC00", "#FFCC33"], Xt.log = console.debug || console.log || (() => {
            }), Xt = function (e) {
                function t(e) {
                    let n, i = null;

                    function s(...e) {
                        if (!s.enabled) return;
                        const r = s, i = Number(new Date), o = i - (n || i);
                        r.diff = o, r.prev = n, r.curr = i, n = i, e[0] = t.coerce(e[0]), "string" != typeof e[0] && e.unshift("%O");
                        let a = 0;
                        e[0] = e[0].replace(/%([a-zA-Z%])/g, (n, i) => {
                            if ("%%" === n) return "%";
                            a++;
                            const s = t.formatters[i];
                            if ("function" == typeof s) {
                                const t = e[a];
                                n = s.call(r, t), e.splice(a, 1), a--
                            }
                            return n
                        }), t.formatArgs.call(r, e), (r.log || t.log).apply(r, e)
                    }

                    return s.namespace = e, s.useColors = t.useColors(), s.color = t.selectColor(e), s.extend = r, s.destroy = t.destroy, Object.defineProperty(s, "enabled", {
                        enumerable: !0,
                        configurable: !1,
                        get: () => null === i ? t.enabled(e) : i,
                        set: e => {
                            i = e
                        }
                    }), "function" == typeof t.init && t.init(s), s
                }

                function r(e, r) {
                    const n = t(this.namespace + (void 0 === r ? ":" : r) + e);
                    return n.log = this.log, n
                }

                function n(e) {
                    return e.toString().substring(2, e.toString().length - 2).replace(/\.\*\?$/, "*")
                }

                return t.debug = t, t.default = t, t.coerce = function (e) {
                    return e instanceof Error ? e.stack || e.message : e
                }, t.disable = function () {
                    const e = [...t.names.map(n), ...t.skips.map(n).map(e => "-" + e)].join(",");
                    return t.enable(""), e
                }, t.enable = function (e) {
                    let r;
                    t.save(e), t.names = [], t.skips = [];
                    const n = ("string" == typeof e ? e : "").split(/[\s,]+/), i = n.length;
                    for (r = 0; r < i; r++) n[r] && ("-" === (e = n[r].replace(/\*/g, ".*?"))[0] ? t.skips.push(new RegExp("^" + e.substr(1) + "$")) : t.names.push(new RegExp("^" + e + "$")))
                }, t.enabled = function (e) {
                    if ("*" === e[e.length - 1]) return !0;
                    let r, n;
                    for (r = 0, n = t.skips.length; r < n; r++) if (t.skips[r].test(e)) return !1;
                    for (r = 0, n = t.names.length; r < n; r++) if (t.names[r].test(e)) return !0;
                    return !1
                }, t.humanize = c({}), t.destroy = function () {
                    console.warn("Instance method `debug.destroy()` is deprecated and no longer does anything. It will be removed in the next major version of `debug`.")
                }, Object.keys(e).forEach(r => {
                    t[r] = e[r]
                }), t.names = [], t.skips = [], t.formatters = {}, t.selectColor = function (e) {
                    let r = 0;
                    for (let t = 0; t < e.length; t++) r = (r << 5) - r + e.charCodeAt(t), r |= 0;
                    return t.colors[Math.abs(r) % t.colors.length]
                }, t.enable(t.load()), t
            }(Xt);
            const {formatters: t} = Xt;
            t.j = function (e) {
                try {
                    return JSON.stringify(e)
                } catch (t) {
                    return "[UnexpectedJSONParseError]: " + t.message
                }
            }
        }).call(this)
    }).call(this, _e);
    var Yt = function (e, t) {
        if ("string" == typeof e) {
            const t = e;
            if (!(e = window.document.querySelector(e))) throw new Error(`"${t}" does not match any HTML elements`)
        }
        if (!e) throw new Error(`"${e}" is not a valid HTML element`);
        let r;
        return "function" == typeof t && (t = {onDrop: t}), e.addEventListener("dragenter", n, !1), e.addEventListener("dragover", i, !1), e.addEventListener("dragleave", s, !1), e.addEventListener("drop", o, !1), function () {
            a(), e.removeEventListener("dragenter", n, !1), e.removeEventListener("dragover", i, !1), e.removeEventListener("dragleave", s, !1), e.removeEventListener("drop", o, !1)
        };

        function n(e) {
            return t.onDragEnter && t.onDragEnter(e), e.stopPropagation(), e.preventDefault(), !1
        }

        function i(n) {
            if (n.stopPropagation(), n.preventDefault(), t.onDragOver && t.onDragOver(n), n.dataTransfer.items || n.dataTransfer.types) {
                const e = Array.from(n.dataTransfer.items), r = Array.from(n.dataTransfer.types);
                let i, s;
                if (e.length ? (i = e.filter(e => "file" === e.kind), s = e.filter(e => "string" === e.kind)) : r.length && (i = r.filter(e => "Files" === e), s = r.filter(e => e.startsWith("text/"))), 0 === i.length && !t.onDropText) return;
                if (0 === s.length && !t.onDrop) return;
                if (0 === i.length && 0 === s.length) return
            }
            return e.classList.add("drag"), clearTimeout(r), n.dataTransfer.dropEffect = "copy", !1
        }

        function s(e) {
            return e.stopPropagation(), e.preventDefault(), t.onDragLeave && t.onDragLeave(e), clearTimeout(r), r = setTimeout(a, 50), !1
        }

        function o(e) {
            e.stopPropagation(), e.preventDefault(), t.onDragLeave && t.onDragLeave(e), clearTimeout(r), a();
            const n = {x: e.clientX, y: e.clientY}, i = e.dataTransfer.getData("text");
            if (i && t.onDropText && t.onDropText(i, n), t.onDrop && e.dataTransfer.items) {
                const r = e.dataTransfer.files, i = Array.from(e.dataTransfer.items).filter(e => "file" === e.kind);
                if (0 === i.length) return;
                Mt(i.map(e => t => {
                    !function (e, t) {
                        let r = [];
                        if (e.isFile) e.file(r => {
                            r.fullPath = e.fullPath, r.isFile = !0, r.isDirectory = !1, t(null, r)
                        }, e => {
                            t(e)
                        }); else if (e.isDirectory) {
                            !function n(i) {
                                i.readEntries(s => {
                                    s.length > 0 ? (r = r.concat(Array.from(s)), n(i)) : Mt(r.map(e => t => {
                                        !function e(t, r) {
                                            let n = [];
                                            if (t.isFile) t.file(e => {
                                                e.fullPath = t.fullPath, e.isFile = !0, e.isDirectory = !1, r(null, e)
                                            }, e => {
                                                r(e)
                                            }); else if (t.isDirectory) {
                                                !function e(t) {
                                                    t.readEntries(r => {
                                                        r.length > 0 ? (n = n.concat(Array.from(r)), e(t)) : i()
                                                    })
                                                }(t.createReader())
                                            }

                                            function i() {
                                                Mt(n.map(t => r => {
                                                    e(t, r)
                                                }), (e, n) => {
                                                    e ? r(e) : (n.push({
                                                        fullPath: t.fullPath,
                                                        name: t.name,
                                                        isFile: !1,
                                                        isDirectory: !0
                                                    }), r(null, n))
                                                })
                                            }
                                        }(e, t)
                                    }), (r, n) => {
                                        r ? t(r) : (n.push({
                                            fullPath: e.fullPath,
                                            name: e.name,
                                            isFile: !1,
                                            isDirectory: !0
                                        }), t(null, n))
                                    })
                                })
                            }(e.createReader())
                        }
                    }(e.webkitGetAsEntry(), t)
                }), (e, i) => {
                    if (e) throw e;
                    const s = i.flat(1 / 0), o = s.filter(e => e.isFile), a = s.filter(e => e.isDirectory);
                    t.onDrop(o, n, r, a)
                })
            }
            return !1
        }

        function a() {
            e.classList.remove("drag")
        }
    };
    var Jt = /["'&<>]/, Qt = function (e) {
        var t, r = "" + e, n = Jt.exec(r);
        if (!n) return r;
        var i = "", s = 0, o = 0;
        for (s = n.index; s < r.length; s++) {
            switch (r.charCodeAt(s)) {
                case 34:
                    t = "&quot;";
                    break;
                case 38:
                    t = "&amp;";
                    break;
                case 39:
                    t = "&#39;";
                    break;
                case 60:
                    t = "&lt;";
                    break;
                case 62:
                    t = "&gt;";
                    break;
                default:
                    continue
            }
            o !== s && (i += r.substring(o, s)), o = s + 1, i += t
        }
        return o !== s ? i + r.substring(o, s) : i
    }, er = {};
    (function (e) {
        (function () {
            er = function (t, r) {
                var n = [];
                t.on("data", (function (e) {
                    n.push(e)
                })), t.once("end", (function () {
                    r && r(null, e.concat(n)), r = null
                })), t.once("error", (function (e) {
                    r && r(e), r = null
                }))
            }
        }).call(this)
    }).call(this, y({}).Buffer);
    var tr = {};
    (function (e) {
        (function () {
            var t;

            function r() {
                if (void 0 !== t) return t;
                if (e.XMLHttpRequest) {
                    t = new e.XMLHttpRequest;
                    try {
                        t.open("GET", e.XDomainRequest ? "/" : "https://example.com")
                    } catch (r) {
                        t = null
                    }
                } else t = null;
                return t
            }

            function n(e) {
                var t = r();
                if (!t) return !1;
                try {
                    return t.responseType = e, t.responseType === e
                } catch (n) {
                }
                return !1
            }

            function i(e) {
                return "function" == typeof e
            }

            tr.fetch = i(e.fetch) && i(e.ReadableStream), tr.writableStream = i(e.WritableStream), tr.abortController = i(e.AbortController), tr.arraybuffer = tr.fetch || n("arraybuffer"), tr.msstream = !tr.fetch && n("ms-stream"), tr.mozchunkedarraybuffer = !tr.fetch && n("moz-chunked-arraybuffer"), tr.overrideMimeType = tr.fetch || !!r() && i(r().overrideMimeType), t = null
        }).call(this)
    }).call(this, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {});
    var rr = {};
    (function (e, t, r) {
        (function () {
            var n = rr.readyStates = {UNSENT: 0, OPENED: 1, HEADERS_RECEIVED: 2, LOADING: 3, DONE: 4},
                i = rr.IncomingMessage = function (n, i, s, o) {
                    var a = this;
                    if (dt.Readable.call(a), a._mode = s, a.headers = {}, a.rawHeaders = [], a.trailers = {}, a.rawTrailers = [], a.on("end", (function () {
                        e.nextTick((function () {
                            a.emit("close")
                        }))
                    })), "fetch" === s) {
                        if (a._fetchResponse = i, a.url = i.url, a.statusCode = i.status, a.statusMessage = i.statusText, i.headers.forEach((function (e, t) {
                            a.headers[t.toLowerCase()] = e, a.rawHeaders.push(t, e)
                        })), tr.writableStream) {
                            var h = new WritableStream({
                                write: function (e) {
                                    return new Promise((function (t, n) {
                                        a._destroyed ? n() : a.push(r.from(e)) ? t() : a._resumeFetch = t
                                    }))
                                }, close: function () {
                                    t.clearTimeout(o), a._destroyed || a.push(null)
                                }, abort: function (e) {
                                    a._destroyed || a.emit("error", e)
                                }
                            });
                            try {
                                return void i.body.pipeTo(h).catch((function (e) {
                                    t.clearTimeout(o), a._destroyed || a.emit("error", e)
                                }))
                            } catch (l) {
                            }
                        }
                        var u = i.body.getReader();
                        !function e() {
                            u.read().then((function (n) {
                                if (!a._destroyed) {
                                    if (n.done) return t.clearTimeout(o), void a.push(null);
                                    a.push(r.from(n.value)), e()
                                }
                            })).catch((function (e) {
                                t.clearTimeout(o), a._destroyed || a.emit("error", e)
                            }))
                        }()
                    } else if (a._xhr = n, a._pos = 0, a.url = n.responseURL, a.statusCode = n.status, a.statusMessage = n.statusText, n.getAllResponseHeaders().split(/\r?\n/).forEach((function (e) {
                        var t = e.match(/^([^:]+):\s*(.*)/);
                        if (t) {
                            var r = t[1].toLowerCase();
                            "set-cookie" === r ? (void 0 === a.headers[r] && (a.headers[r] = []), a.headers[r].push(t[2])) : void 0 !== a.headers[r] ? a.headers[r] += ", " + t[2] : a.headers[r] = t[2], a.rawHeaders.push(t[1], t[2])
                        }
                    })), a._charset = "x-user-defined", !tr.overrideMimeType) {
                        var c = a.rawHeaders["mime-type"];
                        if (c) {
                            var d = c.match(/;\s*charset=([^;])(;|$)/);
                            d && (a._charset = d[1].toLowerCase())
                        }
                        a._charset || (a._charset = "utf-8")
                    }
                };
            De(i, dt.Readable), i.prototype._read = function () {
                var e = this._resumeFetch;
                e && (this._resumeFetch = null, e())
            }, i.prototype._onXHRProgress = function () {
                var e = this, i = e._xhr, s = null;
                switch (e._mode) {
                    case"text":
                        if ((s = i.responseText).length > e._pos) {
                            var o = s.substr(e._pos);
                            if ("x-user-defined" === e._charset) {
                                for (var a = r.alloc(o.length), h = 0; h < o.length; h++) a[h] = 255 & o.charCodeAt(h);
                                e.push(a)
                            } else e.push(o, e._charset);
                            e._pos = s.length
                        }
                        break;
                    case"arraybuffer":
                        if (i.readyState !== n.DONE || !i.response) break;
                        s = i.response, e.push(r.from(new Uint8Array(s)));
                        break;
                    case"moz-chunked-arraybuffer":
                        if (s = i.response, i.readyState !== n.LOADING || !s) break;
                        e.push(r.from(new Uint8Array(s)));
                        break;
                    case"ms-stream":
                        if (s = i.response, i.readyState !== n.LOADING) break;
                        var u = new t.MSStreamReader;
                        u.onprogress = function () {
                            u.result.byteLength > e._pos && (e.push(r.from(new Uint8Array(u.result.slice(e._pos)))), e._pos = u.result.byteLength)
                        }, u.onload = function () {
                            e.push(null)
                        }, u.readAsArrayBuffer(s)
                }
                e._xhr.readyState === n.DONE && "ms-stream" !== e._mode && e.push(null)
            }
        }).call(this)
    }).call(this, _e, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {}, y({}).Buffer);
    var nr = {};
    (function (e, t, r) {
        (function () {
            var n = rr.IncomingMessage, i = rr.readyStates, s = nr = function (e) {
                var t, n = this;
                dt.Writable.call(n), n._opts = e, n._body = [], n._headers = {}, e.auth && n.setHeader("Authorization", "Basic " + r.from(e.auth).toString("base64")), Object.keys(e.headers).forEach((function (t) {
                    n.setHeader(t, e.headers[t])
                }));
                var i = !0;
                if ("disable-fetch" === e.mode || "requestTimeout" in e && !tr.abortController) i = !1, t = !0; else if ("prefer-streaming" === e.mode) t = !1; else if ("allow-wrong-content-type" === e.mode) t = !tr.overrideMimeType; else {
                    if (e.mode && "default" !== e.mode && "prefer-fast" !== e.mode) throw new Error("Invalid value for opts.mode");
                    t = !0
                }
                n._mode = function (e, t) {
                    return tr.fetch && t ? "fetch" : tr.mozchunkedarraybuffer ? "moz-chunked-arraybuffer" : tr.msstream ? "ms-stream" : tr.arraybuffer && e ? "arraybuffer" : "text"
                }(t, i), n._fetchTimer = null, n.on("finish", (function () {
                    n._onFinish()
                }))
            };
            De(s, dt.Writable), s.prototype.setHeader = function (e, t) {
                var r = e.toLowerCase();
                -1 === o.indexOf(r) && (this._headers[r] = {name: e, value: t})
            }, s.prototype.getHeader = function (e) {
                var t = this._headers[e.toLowerCase()];
                return t ? t.value : null
            }, s.prototype.removeHeader = function (e) {
                delete this._headers[e.toLowerCase()]
            }, s.prototype._onFinish = function () {
                var r = this;
                if (!r._destroyed) {
                    var n = r._opts, s = r._headers, o = null;
                    "GET" !== n.method && "HEAD" !== n.method && (o = new Blob(r._body, {type: (s["content-type"] || {}).value || ""}));
                    var a = [];
                    if (Object.keys(s).forEach((function (e) {
                        var t = s[e].name, r = s[e].value;
                        Array.isArray(r) ? r.forEach((function (e) {
                            a.push([t, e])
                        })) : a.push([t, r])
                    })), "fetch" === r._mode) {
                        var h = null;
                        if (tr.abortController) {
                            var u = new AbortController;
                            h = u.signal, r._fetchAbortController = u, "requestTimeout" in n && 0 !== n.requestTimeout && (r._fetchTimer = t.setTimeout((function () {
                                r.emit("requestTimeout"), r._fetchAbortController && r._fetchAbortController.abort()
                            }), n.requestTimeout))
                        }
                        t.fetch(r._opts.url, {
                            method: r._opts.method,
                            headers: a,
                            body: o || void 0,
                            mode: "cors",
                            credentials: n.withCredentials ? "include" : "same-origin",
                            signal: h
                        }).then((function (e) {
                            r._fetchResponse = e, r._connect()
                        }), (function (e) {
                            t.clearTimeout(r._fetchTimer), r._destroyed || r.emit("error", e)
                        }))
                    } else {
                        var c = r._xhr = new t.XMLHttpRequest;
                        try {
                            c.open(r._opts.method, r._opts.url, !0)
                        } catch (Ga) {
                            return void e.nextTick((function () {
                                r.emit("error", Ga)
                            }))
                        }
                        "responseType" in c && (c.responseType = r._mode), "withCredentials" in c && (c.withCredentials = !!n.withCredentials), "text" === r._mode && "overrideMimeType" in c && c.overrideMimeType("text/plain; charset=x-user-defined"), "requestTimeout" in n && (c.timeout = n.requestTimeout, c.ontimeout = function () {
                            r.emit("requestTimeout")
                        }), a.forEach((function (e) {
                            c.setRequestHeader(e[0], e[1])
                        })), r._response = null, c.onreadystatechange = function () {
                            switch (c.readyState) {
                                case i.LOADING:
                                case i.DONE:
                                    r._onXHRProgress()
                            }
                        }, "moz-chunked-arraybuffer" === r._mode && (c.onprogress = function () {
                            r._onXHRProgress()
                        }), c.onerror = function () {
                            r._destroyed || r.emit("error", new Error("XHR error"))
                        };
                        try {
                            c.send(o)
                        } catch (Ga) {
                            return void e.nextTick((function () {
                                r.emit("error", Ga)
                            }))
                        }
                    }
                }
            }, s.prototype._onXHRProgress = function () {
                (function (e) {
                    try {
                        var t = e.status;
                        return null !== t && 0 !== t
                    } catch (r) {
                        return !1
                    }
                })(this._xhr) && !this._destroyed && (this._response || this._connect(), this._response._onXHRProgress())
            }, s.prototype._connect = function () {
                var e = this;
                e._destroyed || (e._response = new n(e._xhr, e._fetchResponse, e._mode, e._fetchTimer), e._response.on("error", (function (t) {
                    e.emit("error", t)
                })), e.emit("response", e._response))
            }, s.prototype._write = function (e, t, r) {
                this._body.push(e), r()
            }, s.prototype.abort = s.prototype.destroy = function () {
                this._destroyed = !0, t.clearTimeout(this._fetchTimer), this._response && (this._response._destroyed = !0), this._xhr ? this._xhr.abort() : this._fetchAbortController && this._fetchAbortController.abort()
            }, s.prototype.end = function (e, t, r) {
                "function" == typeof e && (r = e, e = void 0), dt.Writable.prototype.end.call(this, e, t, r)
            }, s.prototype.flushHeaders = function () {
            }, s.prototype.setTimeout = function () {
            }, s.prototype.setNoDelay = function () {
            }, s.prototype.setSocketKeepAlive = function () {
            };
            var o = ["accept-charset", "accept-encoding", "access-control-request-headers", "access-control-request-method", "connection", "content-length", "cookie", "cookie2", "date", "dnt", "expect", "host", "keep-alive", "origin", "referer", "te", "trailer", "transfer-encoding", "upgrade", "via"]
        }).call(this)
    }).call(this, _e, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {}, y({}).Buffer);
    var ir = Object.prototype.hasOwnProperty, sr = {
        100: "Continue",
        101: "Switching Protocols",
        102: "Processing",
        200: "OK",
        201: "Created",
        202: "Accepted",
        203: "Non-Authoritative Information",
        204: "No Content",
        205: "Reset Content",
        206: "Partial Content",
        207: "Multi-Status",
        208: "Already Reported",
        226: "IM Used",
        300: "Multiple Choices",
        301: "Moved Permanently",
        302: "Found",
        303: "See Other",
        304: "Not Modified",
        305: "Use Proxy",
        307: "Temporary Redirect",
        308: "Permanent Redirect",
        400: "Bad Request",
        401: "Unauthorized",
        402: "Payment Required",
        403: "Forbidden",
        404: "Not Found",
        405: "Method Not Allowed",
        406: "Not Acceptable",
        407: "Proxy Authentication Required",
        408: "Request Timeout",
        409: "Conflict",
        410: "Gone",
        411: "Length Required",
        412: "Precondition Failed",
        413: "Payload Too Large",
        414: "URI Too Long",
        415: "Unsupported Media Type",
        416: "Range Not Satisfiable",
        417: "Expectation Failed",
        418: "I'm a teapot",
        421: "Misdirected Request",
        422: "Unprocessable Entity",
        423: "Locked",
        424: "Failed Dependency",
        425: "Unordered Collection",
        426: "Upgrade Required",
        428: "Precondition Required",
        429: "Too Many Requests",
        431: "Request Header Fields Too Large",
        451: "Unavailable For Legal Reasons",
        500: "Internal Server Error",
        501: "Not Implemented",
        502: "Bad Gateway",
        503: "Service Unavailable",
        504: "Gateway Timeout",
        505: "HTTP Version Not Supported",
        506: "Variant Also Negotiates",
        507: "Insufficient Storage",
        508: "Loop Detected",
        509: "Bandwidth Limit Exceeded",
        510: "Not Extended",
        511: "Network Authentication Required"
    }, or = {exports: {}};
    (function (e) {
        (function () {
            !function (t) {
                var r = "object" == typeof or.exports && or.exports && !or.exports.nodeType && or.exports,
                    n = or && !or.nodeType && or, i = "object" == typeof e && e;
                i.global !== i && i.window !== i && i.self !== i || (t = i);
                var s, o, a = 2147483647, h = /^xn--/, u = /[^\x20-\x7E]/, c = /[\x2E\u3002\uFF0E\uFF61]/g, d = {
                    overflow: "Overflow: input needs wider integers to process",
                    "not-basic": "Illegal input >= 0x80 (not a basic code point)",
                    "invalid-input": "Invalid input"
                }, l = Math.floor, f = String.fromCharCode;

                function p(e) {
                    throw new RangeError(d[e])
                }

                function m(e, t) {
                    for (var r = e.length, n = []; r--;) n[r] = t(e[r]);
                    return n
                }

                function g(e, t) {
                    var r = e.split("@"), n = "";
                    return r.length > 1 && (n = r[0] + "@", e = r[1]), n + m((e = e.replace(c, ".")).split("."), t).join(".")
                }

                function _(e) {
                    for (var t, r, n = [], i = 0, s = e.length; i < s;) (t = e.charCodeAt(i++)) >= 55296 && t <= 56319 && i < s ? 56320 == (64512 & (r = e.charCodeAt(i++))) ? n.push(((1023 & t) << 10) + (1023 & r) + 65536) : (n.push(t), i--) : n.push(t);
                    return n
                }

                function y(e) {
                    return m(e, (function (e) {
                        var t = "";
                        return e > 65535 && (t += f((e -= 65536) >>> 10 & 1023 | 55296), e = 56320 | 1023 & e), t + f(e)
                    })).join("")
                }

                function b(e, t) {
                    return e + 22 + 75 * (e < 26) - ((0 != t) << 5)
                }

                function v(e, t, r) {
                    var n = 0;
                    for (e = r ? l(e / 700) : e >> 1, e += l(e / t); e > 455; n += 36) e = l(e / 35);
                    return l(n + 36 * e / (e + 38))
                }

                function w(e) {
                    var t, r, n, i, s, o, h, u, c, d, f, m = [], g = e.length, _ = 0, b = 128, w = 72;
                    for ((r = e.lastIndexOf("-")) < 0 && (r = 0), n = 0; n < r; ++n) e.charCodeAt(n) >= 128 && p("not-basic"), m.push(e.charCodeAt(n));
                    for (i = r > 0 ? r + 1 : 0; i < g;) {
                        for (s = _, o = 1, h = 36; i >= g && p("invalid-input"), ((u = (f = e.charCodeAt(i++)) - 48 < 10 ? f - 22 : f - 65 < 26 ? f - 65 : f - 97 < 26 ? f - 97 : 36) >= 36 || u > l((a - _) / o)) && p("overflow"), _ += u * o, !(u < (c = h <= w ? 1 : h >= w + 26 ? 26 : h - w)); h += 36) o > l(a / (d = 36 - c)) && p("overflow"), o *= d;
                        w = v(_ - s, t = m.length + 1, 0 == s), l(_ / t) > a - b && p("overflow"), b += l(_ / t), _ %= t, m.splice(_++, 0, b)
                    }
                    return y(m)
                }

                function E(e) {
                    var t, r, n, i, s, o, h, u, c, d, m, g, y, w, E, k = [];
                    for (g = (e = _(e)).length, t = 128, r = 0, s = 72, o = 0; o < g; ++o) (m = e[o]) < 128 && k.push(f(m));
                    for (n = i = k.length, i && k.push("-"); n < g;) {
                        for (h = a, o = 0; o < g; ++o) (m = e[o]) >= t && m < h && (h = m);
                        for (h - t > l((a - r) / (y = n + 1)) && p("overflow"), r += (h - t) * y, t = h, o = 0; o < g; ++o) if ((m = e[o]) < t && ++r > a && p("overflow"), m == t) {
                            for (u = r, c = 36; !(u < (d = c <= s ? 1 : c >= s + 26 ? 26 : c - s)); c += 36) E = u - d, w = 36 - d, k.push(f(b(d + E % w, 0))), u = l(E / w);
                            k.push(f(b(u, 0))), s = v(r, y, n == i), r = 0, ++n
                        }
                        ++r, ++t
                    }
                    return k.join("")
                }

                if (s = {
                    version: "1.4.1", ucs2: {decode: _, encode: y}, decode: w, encode: E, toASCII: function (e) {
                        return g(e, (function (e) {
                            return u.test(e) ? "xn--" + E(e) : e
                        }))
                    }, toUnicode: function (e) {
                        return g(e, (function (e) {
                            return h.test(e) ? w(e.slice(4).toLowerCase()) : e
                        }))
                    }
                }, "function" == typeof define && "object" == typeof define.amd && define.amd) define("punycode", (function () {
                    return s
                })); else if (r && n) if (or.exports == r) n.exports = s; else for (o in s) s.hasOwnProperty(o) && (r[o] = s[o]); else t.punycode = s
            }(this)
        }).call(this)
    }).call(this, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {}), or = or.exports;
    var ar = function (e) {
        return "string" == typeof e
    }, hr = function (e) {
        return "object" == typeof e && null !== e
    }, ur = function (e) {
        return null === e
    }, cr = function (e) {
        return null == e
    };

    function dr(e, t) {
        return Object.prototype.hasOwnProperty.call(e, t)
    }

    var lr = Array.isArray || function (e) {
        return "[object Array]" === Object.prototype.toString.call(e)
    }, fr = function (e) {
        switch (typeof e) {
            case"string":
                return e;
            case"boolean":
                return e ? "true" : "false";
            case"number":
                return isFinite(e) ? e : "";
            default:
                return ""
        }
    }, pr = Array.isArray || function (e) {
        return "[object Array]" === Object.prototype.toString.call(e)
    };

    function mr(e, t) {
        if (e.map) return e.map(t);
        for (var r = [], n = 0; n < e.length; n++) r.push(t(e[n], n));
        return r
    }

    var gr = Object.keys || function (e) {
        var t = [];
        for (var r in e) Object.prototype.hasOwnProperty.call(e, r) && t.push(r);
        return t
    }, _r = {
        parse: function (e, t, r, n) {
            t = t || "&", r = r || "=";
            var i = {};
            if ("string" != typeof e || 0 === e.length) return i;
            var s = /\+/g;
            e = e.split(t);
            var o = 1e3;
            n && "number" == typeof n.maxKeys && (o = n.maxKeys);
            var a = e.length;
            o > 0 && a > o && (a = o);
            for (var h = 0; h < a; ++h) {
                var u, c, d, l, f = e[h].replace(s, "%20"), p = f.indexOf(r);
                p >= 0 ? (u = f.substr(0, p), c = f.substr(p + 1)) : (u = f, c = ""), d = decodeURIComponent(u), l = decodeURIComponent(c), dr(i, d) ? lr(i[d]) ? i[d].push(l) : i[d] = [i[d], l] : i[d] = l
            }
            return i
        }, stringify: function (e, t, r, n) {
            return t = t || "&", r = r || "=", null === e && (e = void 0), "object" == typeof e ? mr(gr(e), (function (n) {
                var i = encodeURIComponent(fr(n)) + r;
                return pr(e[n]) ? mr(e[n], (function (e) {
                    return i + encodeURIComponent(fr(e))
                })).join(t) : i + encodeURIComponent(fr(e[n]))
            })).join(t) : n ? encodeURIComponent(fr(n)) + r + encodeURIComponent(fr(e)) : ""
        }
    }, yr = {};

    function br() {
        this.protocol = null, this.slashes = null, this.auth = null, this.host = null, this.port = null, this.hostname = null, this.hash = null, this.search = null, this.query = null, this.pathname = null, this.path = null, this.href = null
    }

    yr.parse = Lr;
    var vr = /^([a-z0-9.+-]+:)/i, wr = /:[0-9]*$/, Er = /^(\/\/?(?!\/)[^\?\s]*)(\?[^\s]*)?$/,
        kr = ["{", "}", "|", "\\", "^", "`"].concat(["<", ">", '"', "`", " ", "\r", "\n", "\t"]), Sr = ["'"].concat(kr),
        Cr = ["%", "/", "?", ";", "#"].concat(Sr), xr = ["/", "?", "#"], Ar = /^[+a-z0-9A-Z_-]{0,63}$/,
        Tr = /^([+a-z0-9A-Z_-]{0,63})(.*)$/, Ir = {javascript: !0, "javascript:": !0},
        Rr = {javascript: !0, "javascript:": !0}, Br = {
            http: !0,
            https: !0,
            ftp: !0,
            gopher: !0,
            file: !0,
            "http:": !0,
            "https:": !0,
            "ftp:": !0,
            "gopher:": !0,
            "file:": !0
        };

    function Lr(e, t, r) {
        if (e && hr(e) && e instanceof br) return e;
        var n = new br;
        return n.parse(e, t, r), n
    }

    br.prototype.parse = function (e, t, r) {
        if (!ar(e)) throw new TypeError("Parameter 'url' must be a string, not " + typeof e);
        var n = e.indexOf("?"), i = -1 !== n && n < e.indexOf("#") ? "?" : "#", s = e.split(i);
        s[0] = s[0].replace(/\\/g, "/");
        var o = e = s.join(i);
        if (o = o.trim(), !r && 1 === e.split("#").length) {
            var a = Er.exec(o);
            if (a) return this.path = o, this.href = o, this.pathname = a[1], a[2] ? (this.search = a[2], this.query = t ? _r.parse(this.search.substr(1)) : this.search.substr(1)) : t && (this.search = "", this.query = {}), this
        }
        var h = vr.exec(o);
        if (h) {
            var u = (h = h[0]).toLowerCase();
            this.protocol = u, o = o.substr(h.length)
        }
        if (r || h || o.match(/^\/\/[^@\/]+@[^@\/]+/)) {
            var c = "//" === o.substr(0, 2);
            !c || h && Rr[h] || (o = o.substr(2), this.slashes = !0)
        }
        if (!Rr[h] && (c || h && !Br[h])) {
            for (var d, l, f = -1, p = 0; p < xr.length; p++) -1 !== (m = o.indexOf(xr[p])) && (-1 === f || m < f) && (f = m);
            for (-1 !== (l = -1 === f ? o.lastIndexOf("@") : o.lastIndexOf("@", f)) && (d = o.slice(0, l), o = o.slice(l + 1), this.auth = decodeURIComponent(d)), f = -1, p = 0; p < Cr.length; p++) {
                var m;
                -1 !== (m = o.indexOf(Cr[p])) && (-1 === f || m < f) && (f = m)
            }
            -1 === f && (f = o.length), this.host = o.slice(0, f), o = o.slice(f), this.parseHost(), this.hostname = this.hostname || "";
            var g = "[" === this.hostname[0] && "]" === this.hostname[this.hostname.length - 1];
            if (!g) for (var _ = this.hostname.split(/\./), y = (p = 0, _.length); p < y; p++) {
                var b = _[p];
                if (b && !b.match(Ar)) {
                    for (var v = "", w = 0, E = b.length; w < E; w++) b.charCodeAt(w) > 127 ? v += "x" : v += b[w];
                    if (!v.match(Ar)) {
                        var k = _.slice(0, p), S = _.slice(p + 1), C = b.match(Tr);
                        C && (k.push(C[1]), S.unshift(C[2])), S.length && (o = "/" + S.join(".") + o), this.hostname = k.join(".");
                        break
                    }
                }
            }
            this.hostname.length > 255 ? this.hostname = "" : this.hostname = this.hostname.toLowerCase(), g || (this.hostname = or.toASCII(this.hostname));
            var x = this.port ? ":" + this.port : "", A = this.hostname || "";
            this.host = A + x, this.href += this.host, g && (this.hostname = this.hostname.substr(1, this.hostname.length - 2), "/" !== o[0] && (o = "/" + o))
        }
        if (!Ir[u]) for (p = 0, y = Sr.length; p < y; p++) {
            var T = Sr[p];
            if (-1 !== o.indexOf(T)) {
                var I = encodeURIComponent(T);
                I === T && (I = escape(T)), o = o.split(T).join(I)
            }
        }
        var R = o.indexOf("#");
        -1 !== R && (this.hash = o.substr(R), o = o.slice(0, R));
        var B = o.indexOf("?");
        if (-1 !== B ? (this.search = o.substr(B), this.query = o.substr(B + 1), t && (this.query = _r.parse(this.query)), o = o.slice(0, B)) : t && (this.search = "", this.query = {}), o && (this.pathname = o), Br[u] && this.hostname && !this.pathname && (this.pathname = "/"), this.pathname || this.search) {
            x = this.pathname || "";
            var L = this.search || "";
            this.path = x + L
        }
        return this.href = this.format(), this
    }, br.prototype.format = function () {
        var e = this.auth || "";
        e && (e = (e = encodeURIComponent(e)).replace(/%3A/i, ":"), e += "@");
        var t = this.protocol || "", r = this.pathname || "", n = this.hash || "", i = !1, s = "";
        this.host ? i = e + this.host : this.hostname && (i = e + (-1 === this.hostname.indexOf(":") ? this.hostname : "[" + this.hostname + "]"), this.port && (i += ":" + this.port)), this.query && hr(this.query) && Object.keys(this.query).length && (s = _r.stringify(this.query));
        var o = this.search || s && "?" + s || "";
        return t && ":" !== t.substr(-1) && (t += ":"), this.slashes || (!t || Br[t]) && !1 !== i ? (i = "//" + (i || ""), r && "/" !== r.charAt(0) && (r = "/" + r)) : i || (i = ""), n && "#" !== n.charAt(0) && (n = "#" + n), o && "?" !== o.charAt(0) && (o = "?" + o), t + i + (r = r.replace(/[?#]/g, (function (e) {
            return encodeURIComponent(e)
        }))) + (o = o.replace("#", "%23")) + n
    }, br.prototype.resolve = function (e) {
        return this.resolveObject(Lr(e, !1, !0)).format()
    }, br.prototype.resolveObject = function (e) {
        if (ar(e)) {
            var t = new br;
            t.parse(e, !1, !0), e = t
        }
        for (var r = new br, n = Object.keys(this), i = 0; i < n.length; i++) {
            var s = n[i];
            r[s] = this[s]
        }
        if (r.hash = e.hash, "" === e.href) return r.href = r.format(), r;
        if (e.slashes && !e.protocol) {
            for (var o = Object.keys(e), a = 0; a < o.length; a++) {
                var h = o[a];
                "protocol" !== h && (r[h] = e[h])
            }
            return Br[r.protocol] && r.hostname && !r.pathname && (r.path = r.pathname = "/"), r.href = r.format(), r
        }
        if (e.protocol && e.protocol !== r.protocol) {
            if (!Br[e.protocol]) {
                for (var u = Object.keys(e), c = 0; c < u.length; c++) {
                    var d = u[c];
                    r[d] = e[d]
                }
                return r.href = r.format(), r
            }
            if (r.protocol = e.protocol, e.host || Rr[e.protocol]) r.pathname = e.pathname; else {
                for (var l = (e.pathname || "").split("/"); l.length && !(e.host = l.shift());) ;
                e.host || (e.host = ""), e.hostname || (e.hostname = ""), "" !== l[0] && l.unshift(""), l.length < 2 && l.unshift(""), r.pathname = l.join("/")
            }
            if (r.search = e.search, r.query = e.query, r.host = e.host || "", r.auth = e.auth, r.hostname = e.hostname || e.host, r.port = e.port, r.pathname || r.search) {
                var f = r.pathname || "", p = r.search || "";
                r.path = f + p
            }
            return r.slashes = r.slashes || e.slashes, r.href = r.format(), r
        }
        var m = r.pathname && "/" === r.pathname.charAt(0), g = e.host || e.pathname && "/" === e.pathname.charAt(0),
            _ = g || m || r.host && e.pathname, y = _, b = r.pathname && r.pathname.split("/") || [],
            v = (l = e.pathname && e.pathname.split("/") || [], r.protocol && !Br[r.protocol]);
        if (v && (r.hostname = "", r.port = null, r.host && ("" === b[0] ? b[0] = r.host : b.unshift(r.host)), r.host = "", e.protocol && (e.hostname = null, e.port = null, e.host && ("" === l[0] ? l[0] = e.host : l.unshift(e.host)), e.host = null), _ = _ && ("" === l[0] || "" === b[0])), g) r.host = e.host || "" === e.host ? e.host : r.host, r.hostname = e.hostname || "" === e.hostname ? e.hostname : r.hostname, r.search = e.search, r.query = e.query, b = l; else if (l.length) b || (b = []), b.pop(), b = b.concat(l), r.search = e.search, r.query = e.query; else if (!cr(e.search)) return v && (r.hostname = r.host = b.shift(), (C = !!(r.host && r.host.indexOf("@") > 0) && r.host.split("@")) && (r.auth = C.shift(), r.host = r.hostname = C.shift())), r.search = e.search, r.query = e.query, ur(r.pathname) && ur(r.search) || (r.path = (r.pathname ? r.pathname : "") + (r.search ? r.search : "")), r.href = r.format(), r;
        if (!b.length) return r.pathname = null, r.search ? r.path = "/" + r.search : r.path = null, r.href = r.format(), r;
        for (var w = b.slice(-1)[0], E = (r.host || e.host || b.length > 1) && ("." === w || ".." === w) || "" === w, k = 0, S = b.length; S >= 0; S--) "." === (w = b[S]) ? b.splice(S, 1) : ".." === w ? (b.splice(S, 1), k++) : k && (b.splice(S, 1), k--);
        if (!_ && !y) for (; k--; k) b.unshift("..");
        !_ || "" === b[0] || b[0] && "/" === b[0].charAt(0) || b.unshift(""), E && "/" !== b.join("/").substr(-1) && b.push("");
        var C, x = "" === b[0] || b[0] && "/" === b[0].charAt(0);
        return v && (r.hostname = r.host = x ? "" : b.length ? b.shift() : "", (C = !!(r.host && r.host.indexOf("@") > 0) && r.host.split("@")) && (r.auth = C.shift(), r.host = r.hostname = C.shift())), (_ = _ || r.host && b.length) && !x && b.unshift(""), b.length ? r.pathname = b.join("/") : (r.pathname = null, r.path = null), ur(r.pathname) && ur(r.search) || (r.path = (r.pathname ? r.pathname : "") + (r.search ? r.search : "")), r.auth = e.auth || r.auth, r.slashes = r.slashes || e.slashes, r.href = r.format(), r
    }, br.prototype.parseHost = function () {
        var e = this.host, t = wr.exec(e);
        t && (":" !== (t = t[0]) && (this.port = t.substr(1)), e = e.substr(0, e.length - t.length)), e && (this.hostname = e)
    };
    var Or = {};
    (function (e) {
        (function () {
            var t = Or;
            t.request = function (t, r) {
                t = "string" == typeof t ? yr.parse(t) : function () {
                    for (var e = {}, t = 0; t < arguments.length; t++) {
                        var r = arguments[t];
                        for (var n in r) ir.call(r, n) && (e[n] = r[n])
                    }
                    return e
                }(t);
                var n = -1 === e.location.protocol.search(/^https?:$/) ? "http:" : "", i = t.protocol || n,
                    s = t.hostname || t.host, o = t.port, a = t.path || "/";
                s && -1 !== s.indexOf(":") && (s = "[" + s + "]"), t.url = (s ? i + "//" + s : "") + (o ? ":" + o : "") + a, t.method = (t.method || "GET").toUpperCase(), t.headers = t.headers || {};
                var h = new nr(t);
                return r && h.on("response", r), h
            }, t.get = function (e, r) {
                var n = t.request(e, r);
                return n.end(), n
            }, t.ClientRequest = nr, t.IncomingMessage = rr.IncomingMessage, t.Agent = function () {
            }, t.Agent.defaultMaxSockets = 4, t.globalAgent = new t.Agent, t.STATUS_CODES = sr, t.METHODS = ["CHECKOUT", "CONNECT", "COPY", "DELETE", "GET", "HEAD", "LOCK", "M-SEARCH", "MERGE", "MKACTIVITY", "MKCOL", "MOVE", "NOTIFY", "OPTIONS", "PATCH", "POST", "PROPFIND", "PROPPATCH", "PURGE", "PUT", "REPORT", "SEARCH", "SUBSCRIBE", "TRACE", "UNLOCK", "UNSUBSCRIBE"]
        }).call(this)
    }).call(this, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {});
    var Ur = {}, Pr = Ur;
    for (var Mr in Or) Or.hasOwnProperty(Mr) && (Pr[Mr] = Or[Mr]);

    function Dr(e) {
        if ("string" == typeof e && (e = yr.parse(e)), e.protocol || (e.protocol = "https:"), "https:" !== e.protocol) throw new Error('Protocol "' + e.protocol + '" not supported. Expected "https:"');
        return e
    }

    Pr.request = function (e, t) {
        return e = Dr(e), Or.request.call(this, e, t)
    }, Pr.get = function (e, t) {
        return e = Dr(e), Or.get.call(this, e, t)
    };
    var Nr = {};
    (function (e) {
        (function () {
            Nr = r;
            const t = e => null !== e && "object" == typeof e && "function" == typeof e.pipe;

            function r(n, i) {
                if (n = Object.assign({maxRedirects: 10}, "string" == typeof n ? {url: n} : n), i = Ot(i), n.url) {
                    const {hostname: e, port: t, protocol: r, auth: i, path: s} = yr.parse(n.url);
                    delete n.url, e || t || r || i ? Object.assign(n, {
                        hostname: e,
                        port: t,
                        protocol: r,
                        auth: i,
                        path: s
                    }) : n.path = s
                }
                const s = {"accept-encoding": "gzip, deflate"};
                let o;
                n.headers && Object.keys(n.headers).forEach(e => s[e.toLowerCase()] = n.headers[e]), n.headers = s, n.body ? o = n.json && !t(n.body) ? JSON.stringify(n.body) : n.body : n.form && (o = "string" == typeof n.form ? n.form : _r.stringify(n.form), n.headers["content-type"] = "application/x-www-form-urlencoded"), o && (n.method || (n.method = "POST"), t(o) || (n.headers["content-length"] = e.byteLength(o)), n.json && !n.form && (n.headers["content-type"] = "application/json")), delete n.body, delete n.form, n.json && (n.headers.accept = "application/json"), n.method && (n.method = n.method.toUpperCase());
                const a = ("https:" === n.protocol ? Ur : Or).request(n, e => {
                    if (!1 !== n.followRedirects && e.statusCode >= 300 && e.statusCode < 400 && e.headers.location) return n.url = e.headers.location, delete n.headers.host, e.resume(), "POST" === n.method && [301, 302].includes(e.statusCode) && (n.method = "GET", delete n.headers["content-length"], delete n.headers["content-type"]), 0 == n.maxRedirects-- ? i(new Error("too many redirects")) : r(n, i);
                    const t = "function" == typeof ae && "HEAD" !== n.method;
                    i(null, t ? ae(e) : e)
                });
                return a.on("timeout", () => {
                    a.abort(), i(new Error("Request timed out"))
                }), a.on("error", i), t(o) ? o.on("error", i).pipe(a) : a.end(o), a
            }

            r.concat = (e, t) => r(e, (r, n) => {
                if (r) return t(r);
                er(n, (r, i) => {
                    if (r) return t(r);
                    if (e.json) try {
                        i = JSON.parse(i.toString())
                    } catch (r) {
                        return t(r, n, i)
                    }
                    t(null, n, i)
                })
            }), ["get", "post", "put", "patch", "head", "delete"].forEach(e => {
                r[e] = (t, n) => ("string" == typeof t && (t = {url: t}), r(Object.assign({method: e.toUpperCase()}, t), n))
            })
        }).call(this)
    }).call(this, y({}).Buffer);
    var jr = {};
    Object.defineProperty(jr, "__esModule", {value: !0}), jr.default = function (e, t) {
        if (t.length < e) throw new TypeError(e + " argument" + (e > 1 ? "s" : "") + " required, but only " + t.length + " present")
    }, jr = jr.default;
    var Fr = {};
    Object.defineProperty(Fr, "__esModule", {value: !0}), Fr.default = function (e) {
        (0, Hr.default)(1, arguments);
        var t = Object.prototype.toString.call(e);
        return e instanceof Date || "object" == typeof e && "[object Date]" === t ? new Date(e.getTime()) : "number" == typeof e || "[object Number]" === t ? new Date(e) : ("string" != typeof e && "[object String]" !== t || "undefined" == typeof console || (console.warn("Starting with v2.0.0-beta.1 date-fns doesn't accept strings as date arguments. Please use `parseISO` to parse strings. See: https://git.io/fjule"), console.warn((new Error).stack)), new Date(NaN))
    };
    var zr, Hr = (zr = jr) && zr.__esModule ? zr : {default: zr};
    Fr = Fr.default;
    var Wr = {};
    Object.defineProperty(Wr, "__esModule", {value: !0}), Wr.default = function (e, t) {
        (0, Zr.default)(2, arguments);
        var r = (0, qr.default)(e), n = (0, qr.default)(t), i = r.getTime() - n.getTime();
        return i < 0 ? -1 : i > 0 ? 1 : i
    };
    var qr = Vr(Fr), Zr = Vr(jr);

    function Vr(e) {
        return e && e.__esModule ? e : {default: e}
    }

    Wr = Wr.default;
    var $r = {};
    Object.defineProperty($r, "__esModule", {value: !0}), $r.default = function (e, t) {
        (0, Gr.default)(2, arguments);
        var r = (0, Kr.default)(e), n = (0, Kr.default)(t), i = r.getFullYear() - n.getFullYear(),
            s = r.getMonth() - n.getMonth();
        return 12 * i + s
    };
    var Kr = Xr(Fr), Gr = Xr(jr);

    function Xr(e) {
        return e && e.__esModule ? e : {default: e}
    }

    $r = $r.default;
    var Yr = {};
    Object.defineProperty(Yr, "__esModule", {value: !0}), Yr.default = function (e, t) {
        (0, tn.default)(2, arguments);
        var r = (0, Jr.default)(e), n = (0, Jr.default)(t), i = (0, en.default)(r, n),
            s = Math.abs((0, Qr.default)(r, n));
        r.setMonth(r.getMonth() - i * s);
        var o = (0, en.default)(r, n) === -i, a = i * (s - o);
        return 0 === a ? 0 : a
    };
    var Jr = rn(Fr), Qr = rn($r), en = rn(Wr), tn = rn(jr);

    function rn(e) {
        return e && e.__esModule ? e : {default: e}
    }

    Yr = Yr.default;
    var nn = {};
    Object.defineProperty(nn, "__esModule", {value: !0}), nn.default = function (e, t) {
        (0, on.default)(2, arguments);
        var r = (0, sn.default)(e), n = (0, sn.default)(t);
        return r.getTime() - n.getTime()
    };
    var sn = an(Fr), on = an(jr);

    function an(e) {
        return e && e.__esModule ? e : {default: e}
    }

    nn = nn.default;
    var hn = {};
    Object.defineProperty(hn, "__esModule", {value: !0}), hn.default = function (e, t) {
        (0, cn.default)(2, arguments);
        var r = (0, un.default)(e, t) / 1e3;
        return r > 0 ? Math.floor(r) : Math.ceil(r)
    };
    var un = dn(nn), cn = dn(jr);

    function dn(e) {
        return e && e.__esModule ? e : {default: e}
    }

    hn = hn.default;
    var ln = {};
    Object.defineProperty(ln, "__esModule", {value: !0}), ln.default = function (e, t, r) {
        var n;
        return r = r || {}, n = "string" == typeof fn[e] ? fn[e] : 1 === t ? fn[e].one : fn[e].other.replace("{{count}}", t), r.addSuffix ? r.comparison > 0 ? "in " + n : n + " ago" : n
    };
    var fn = {
        lessThanXSeconds: {one: "less than a second", other: "less than {{count}} seconds"},
        xSeconds: {one: "1 second", other: "{{count}} seconds"},
        halfAMinute: "half a minute",
        lessThanXMinutes: {one: "less than a minute", other: "less than {{count}} minutes"},
        xMinutes: {one: "1 minute", other: "{{count}} minutes"},
        aboutXHours: {one: "about 1 hour", other: "about {{count}} hours"},
        xHours: {one: "1 hour", other: "{{count}} hours"},
        xDays: {one: "1 day", other: "{{count}} days"},
        aboutXWeeks: {one: "about 1 week", other: "about {{count}} weeks"},
        xWeeks: {one: "1 week", other: "{{count}} weeks"},
        aboutXMonths: {one: "about 1 month", other: "about {{count}} months"},
        xMonths: {one: "1 month", other: "{{count}} months"},
        aboutXYears: {one: "about 1 year", other: "about {{count}} years"},
        xYears: {one: "1 year", other: "{{count}} years"},
        overXYears: {one: "over 1 year", other: "over {{count}} years"},
        almostXYears: {one: "almost 1 year", other: "almost {{count}} years"}
    };
    ln = ln.default;
    var pn = {};
    Object.defineProperty(pn, "__esModule", {value: !0}), pn.default = function (e) {
        return function (t) {
            var r = t || {}, n = r.width ? String(r.width) : e.defaultWidth;
            return e.formats[n] || e.formats[e.defaultWidth]
        }
    }, pn = pn.default;
    var mn = {};
    Object.defineProperty(mn, "__esModule", {value: !0}), mn.default = void 0;
    var gn, _n = (gn = pn) && gn.__esModule ? gn : {default: gn}, yn = {
        date: (0, _n.default)({
            formats: {
                full: "EEEE, MMMM do, y",
                long: "MMMM do, y",
                medium: "MMM d, y",
                short: "MM/dd/yyyy"
            }, defaultWidth: "full"
        }),
        time: (0, _n.default)({
            formats: {
                full: "h:mm:ss a zzzz",
                long: "h:mm:ss a z",
                medium: "h:mm:ss a",
                short: "h:mm a"
            }, defaultWidth: "full"
        }),
        dateTime: (0, _n.default)({
            formats: {
                full: "{{date}} 'at' {{time}}",
                long: "{{date}} 'at' {{time}}",
                medium: "{{date}}, {{time}}",
                short: "{{date}}, {{time}}"
            }, defaultWidth: "full"
        })
    };
    mn.default = yn, mn = mn.default;
    var bn = {};
    Object.defineProperty(bn, "__esModule", {value: !0}), bn.default = function (e, t, r, n) {
        return vn[e]
    };
    var vn = {
        lastWeek: "'last' eeee 'at' p",
        yesterday: "'yesterday at' p",
        today: "'today at' p",
        tomorrow: "'tomorrow at' p",
        nextWeek: "eeee 'at' p",
        other: "P"
    };
    bn = bn.default;
    var wn = {};
    Object.defineProperty(wn, "__esModule", {value: !0}), wn.default = function (e) {
        return function (t, r) {
            var n, i = r || {};
            if ("formatting" === (i.context ? String(i.context) : "standalone") && e.formattingValues) {
                var s = e.defaultFormattingWidth || e.defaultWidth, o = i.width ? String(i.width) : s;
                n = e.formattingValues[o] || e.formattingValues[s]
            } else {
                var a = e.defaultWidth, h = i.width ? String(i.width) : e.defaultWidth;
                n = e.values[h] || e.values[a]
            }
            return n[e.argumentCallback ? e.argumentCallback(t) : t]
        }
    }, wn = wn.default;
    var En = {};
    Object.defineProperty(En, "__esModule", {value: !0}), En.default = void 0;
    var kn, Sn = (kn = wn) && kn.__esModule ? kn : {default: kn}, Cn = {
        ordinalNumber: function (e, t) {
            var r = Number(e), n = r % 100;
            if (n > 20 || n < 10) switch (n % 10) {
                case 1:
                    return r + "st";
                case 2:
                    return r + "nd";
                case 3:
                    return r + "rd"
            }
            return r + "th"
        },
        era: (0, Sn.default)({
            values: {
                narrow: ["B", "A"],
                abbreviated: ["BC", "AD"],
                wide: ["Before Christ", "Anno Domini"]
            }, defaultWidth: "wide"
        }),
        quarter: (0, Sn.default)({
            values: {
                narrow: ["1", "2", "3", "4"],
                abbreviated: ["Q1", "Q2", "Q3", "Q4"],
                wide: ["1st quarter", "2nd quarter", "3rd quarter", "4th quarter"]
            }, defaultWidth: "wide", argumentCallback: function (e) {
                return Number(e) - 1
            }
        }),
        month: (0, Sn.default)({
            values: {
                narrow: ["J", "F", "M", "A", "M", "J", "J", "A", "S", "O", "N", "D"],
                abbreviated: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                wide: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]
            }, defaultWidth: "wide"
        }),
        day: (0, Sn.default)({
            values: {
                narrow: ["S", "M", "T", "W", "T", "F", "S"],
                short: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
                abbreviated: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                wide: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]
            }, defaultWidth: "wide"
        }),
        dayPeriod: (0, Sn.default)({
            values: {
                narrow: {
                    am: "a",
                    pm: "p",
                    midnight: "mi",
                    noon: "n",
                    morning: "morning",
                    afternoon: "afternoon",
                    evening: "evening",
                    night: "night"
                },
                abbreviated: {
                    am: "AM",
                    pm: "PM",
                    midnight: "midnight",
                    noon: "noon",
                    morning: "morning",
                    afternoon: "afternoon",
                    evening: "evening",
                    night: "night"
                },
                wide: {
                    am: "a.m.",
                    pm: "p.m.",
                    midnight: "midnight",
                    noon: "noon",
                    morning: "morning",
                    afternoon: "afternoon",
                    evening: "evening",
                    night: "night"
                }
            },
            defaultWidth: "wide",
            formattingValues: {
                narrow: {
                    am: "a",
                    pm: "p",
                    midnight: "mi",
                    noon: "n",
                    morning: "in the morning",
                    afternoon: "in the afternoon",
                    evening: "in the evening",
                    night: "at night"
                },
                abbreviated: {
                    am: "AM",
                    pm: "PM",
                    midnight: "midnight",
                    noon: "noon",
                    morning: "in the morning",
                    afternoon: "in the afternoon",
                    evening: "in the evening",
                    night: "at night"
                },
                wide: {
                    am: "a.m.",
                    pm: "p.m.",
                    midnight: "midnight",
                    noon: "noon",
                    morning: "in the morning",
                    afternoon: "in the afternoon",
                    evening: "in the evening",
                    night: "at night"
                }
            },
            defaultFormattingWidth: "wide"
        })
    };
    En.default = Cn, En = En.default;
    var xn = {};
    Object.defineProperty(xn, "__esModule", {value: !0}), xn.default = function (e) {
        return function (t, r) {
            var n = String(t), i = r || {}, s = n.match(e.matchPattern);
            if (!s) return null;
            var o = s[0], a = n.match(e.parsePattern);
            if (!a) return null;
            var h = e.valueCallback ? e.valueCallback(a[0]) : a[0];
            return {value: h = i.valueCallback ? i.valueCallback(h) : h, rest: n.slice(o.length)}
        }
    }, xn = xn.default;
    var An = {};
    Object.defineProperty(An, "__esModule", {value: !0}), An.default = function (e) {
        return function (t, r) {
            var n = String(t), i = r || {}, s = i.width,
                o = s && e.matchPatterns[s] || e.matchPatterns[e.defaultMatchWidth], a = n.match(o);
            if (!a) return null;
            var h, u = a[0], c = s && e.parsePatterns[s] || e.parsePatterns[e.defaultParseWidth];
            return h = "[object Array]" === Object.prototype.toString.call(c) ? function (e, t) {
                for (var r = 0; r < e.length; r++) if (e[r].test(u)) return r
            }(c) : function (e, t) {
                for (var r in e) if (e.hasOwnProperty(r) && e[r].test(u)) return r
            }(c), h = e.valueCallback ? e.valueCallback(h) : h, {
                value: h = i.valueCallback ? i.valueCallback(h) : h,
                rest: n.slice(u.length)
            }
        }
    }, An = An.default;
    var Tn = {};
    Object.defineProperty(Tn, "__esModule", {value: !0}), Tn.default = void 0;
    var In = Bn(xn), Rn = Bn(An);

    function Bn(e) {
        return e && e.__esModule ? e : {default: e}
    }

    var Ln = {
        ordinalNumber: (0, In.default)({
            matchPattern: /^(\d+)(th|st|nd|rd)?/i,
            parsePattern: /\d+/i,
            valueCallback: function (e) {
                return parseInt(e, 10)
            }
        }),
        era: (0, Rn.default)({
            matchPatterns: {
                narrow: /^(b|a)/i,
                abbreviated: /^(b\.?\s?c\.?|b\.?\s?c\.?\s?e\.?|a\.?\s?d\.?|c\.?\s?e\.?)/i,
                wide: /^(before christ|before common era|anno domini|common era)/i
            }, defaultMatchWidth: "wide", parsePatterns: {any: [/^b/i, /^(a|c)/i]}, defaultParseWidth: "any"
        }),
        quarter: (0, Rn.default)({
            matchPatterns: {
                narrow: /^[1234]/i,
                abbreviated: /^q[1234]/i,
                wide: /^[1234](th|st|nd|rd)? quarter/i
            },
            defaultMatchWidth: "wide",
            parsePatterns: {any: [/1/i, /2/i, /3/i, /4/i]},
            defaultParseWidth: "any",
            valueCallback: function (e) {
                return e + 1
            }
        }),
        month: (0, Rn.default)({
            matchPatterns: {
                narrow: /^[jfmasond]/i,
                abbreviated: /^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i,
                wide: /^(january|february|march|april|may|june|july|august|september|october|november|december)/i
            },
            defaultMatchWidth: "wide",
            parsePatterns: {
                narrow: [/^j/i, /^f/i, /^m/i, /^a/i, /^m/i, /^j/i, /^j/i, /^a/i, /^s/i, /^o/i, /^n/i, /^d/i],
                any: [/^ja/i, /^f/i, /^mar/i, /^ap/i, /^may/i, /^jun/i, /^jul/i, /^au/i, /^s/i, /^o/i, /^n/i, /^d/i]
            },
            defaultParseWidth: "any"
        }),
        day: (0, Rn.default)({
            matchPatterns: {
                narrow: /^[smtwf]/i,
                short: /^(su|mo|tu|we|th|fr|sa)/i,
                abbreviated: /^(sun|mon|tue|wed|thu|fri|sat)/i,
                wide: /^(sunday|monday|tuesday|wednesday|thursday|friday|saturday)/i
            },
            defaultMatchWidth: "wide",
            parsePatterns: {
                narrow: [/^s/i, /^m/i, /^t/i, /^w/i, /^t/i, /^f/i, /^s/i],
                any: [/^su/i, /^m/i, /^tu/i, /^w/i, /^th/i, /^f/i, /^sa/i]
            },
            defaultParseWidth: "any"
        }),
        dayPeriod: (0, Rn.default)({
            matchPatterns: {
                narrow: /^(a|p|mi|n|(in the|at) (morning|afternoon|evening|night))/i,
                any: /^([ap]\.?\s?m\.?|midnight|noon|(in the|at) (morning|afternoon|evening|night))/i
            },
            defaultMatchWidth: "any",
            parsePatterns: {
                any: {
                    am: /^a/i,
                    pm: /^p/i,
                    midnight: /^mi/i,
                    noon: /^no/i,
                    morning: /morning/i,
                    afternoon: /afternoon/i,
                    evening: /evening/i,
                    night: /night/i
                }
            },
            defaultParseWidth: "any"
        })
    };
    Tn.default = Ln, Tn = Tn.default;
    var On = {};
    Object.defineProperty(On, "__esModule", {value: !0}), On.default = void 0;
    var Un = jn(ln), Pn = jn(mn), Mn = jn(bn), Dn = jn(En), Nn = jn(Tn);

    function jn(e) {
        return e && e.__esModule ? e : {default: e}
    }

    var Fn = {
        code: "en-US",
        formatDistance: Un.default,
        formatLong: Pn.default,
        formatRelative: Mn.default,
        localize: Dn.default,
        match: Nn.default,
        options: {weekStartsOn: 0, firstWeekContainsDate: 1}
    };
    On.default = Fn, On = On.default;
    var zn = {};
    Object.defineProperty(zn, "__esModule", {value: !0}), zn.default = function (e, t) {
        if (null == e) throw new TypeError("assign requires that input parameter not be null or undefined");
        for (var r in t = t || {}) t.hasOwnProperty(r) && (e[r] = t[r]);
        return e
    }, zn = zn.default;
    var Hn = {};
    Object.defineProperty(Hn, "__esModule", {value: !0}), Hn.default = function (e) {
        return (0, qn.default)({}, e)
    };
    var Wn, qn = (Wn = zn) && Wn.__esModule ? Wn : {default: Wn};
    Hn = Hn.default;
    var Zn = {};
    Object.defineProperty(Zn, "__esModule", {value: !0}), Zn.default = function (e) {
        var t = new Date(e.getTime()), r = Math.ceil(t.getTimezoneOffset());
        t.setSeconds(0, 0);
        var n = r > 0 ? (Vn + $n(t)) % Vn : $n(t);
        return r * Vn + n
    };
    var Vn = 6e4;

    function $n(e) {
        return e.getTime() % Vn
    }

    Zn = Zn.default;
    var Kn = {};
    Object.defineProperty(Kn, "__esModule", {value: !0}), Kn.default = function (e, t, r) {
        (0, ri.default)(2, arguments);
        var n = r || {}, i = n.locale || Jn.default;
        if (!i.formatDistance) throw new RangeError("locale must contain formatDistance property");
        var s = (0, Gn.default)(e, t);
        if (isNaN(s)) throw new RangeError("Invalid time value");
        var o, a, h = (0, ei.default)(n);
        h.addSuffix = Boolean(n.addSuffix), h.comparison = s, s > 0 ? (o = (0, Qn.default)(t), a = (0, Qn.default)(e)) : (o = (0, Qn.default)(e), a = (0, Qn.default)(t));
        var u, c = (0, Yn.default)(a, o), d = ((0, ti.default)(a) - (0, ti.default)(o)) / 1e3,
            l = Math.round((c - d) / 60);
        if (l < 2) return n.includeSeconds ? c < 5 ? i.formatDistance("lessThanXSeconds", 5, h) : c < 10 ? i.formatDistance("lessThanXSeconds", 10, h) : c < 20 ? i.formatDistance("lessThanXSeconds", 20, h) : c < 40 ? i.formatDistance("halfAMinute", null, h) : c < 60 ? i.formatDistance("lessThanXMinutes", 1, h) : i.formatDistance("xMinutes", 1, h) : 0 === l ? i.formatDistance("lessThanXMinutes", 1, h) : i.formatDistance("xMinutes", l, h);
        if (l < 45) return i.formatDistance("xMinutes", l, h);
        if (l < 90) return i.formatDistance("aboutXHours", 1, h);
        if (l < ii) {
            var f = Math.round(l / 60);
            return i.formatDistance("aboutXHours", f, h)
        }
        if (l < si) return i.formatDistance("xDays", 1, h);
        if (l < oi) {
            var p = Math.round(l / ii);
            return i.formatDistance("xDays", p, h)
        }
        if (l < ai) return u = Math.round(l / oi), i.formatDistance("aboutXMonths", u, h);
        if ((u = (0, Xn.default)(a, o)) < 12) {
            var m = Math.round(l / oi);
            return i.formatDistance("xMonths", m, h)
        }
        var g = u % 12, _ = Math.floor(u / 12);
        return g < 3 ? i.formatDistance("aboutXYears", _, h) : g < 9 ? i.formatDistance("overXYears", _, h) : i.formatDistance("almostXYears", _ + 1, h)
    };
    var Gn = ni(Wr), Xn = ni(Yr), Yn = ni(hn), Jn = ni(On), Qn = ni(Fr), ei = ni(Hn), ti = ni(Zn), ri = ni(jr);

    function ni(e) {
        return e && e.__esModule ? e : {default: e}
    }

    var ii = 1440, si = 2520, oi = 43200, ai = 86400;
    Kn = Kn.default;
    var hi = function (e) {
        if ("number" != typeof e || isNaN(e)) throw new TypeError("Expected a number, got " + typeof e);
        var t = e < 0, r = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
        if (t && (e = -e), e < 1) return (t ? "-" : "") + e + " B";
        var n = Math.min(Math.floor(Math.log(e) / Math.log(1e3)), r.length - 1);
        e = Number(e / Math.pow(1e3, n));
        var i = r[n];
        return e >= 10 || e % 1 == 0 ? (t ? "-" : "") + e.toFixed(0) + " " + i : (t ? "-" : "") + e.toFixed(1) + " " + i
    }, ui = function (e, t) {
        var r, n, i, s, o = 0;
        return function () {
            r = this, n = arguments;
            var e = new Date - o;
            return s || (e >= t ? a() : s = setTimeout(a, t - e)), i
        };

        function a() {
            s = 0, o = +new Date, i = e.apply(r, n), r = null, n = null
        }
    }, ci = {};
    (function (e) {
        (function () {
            "use strict";
            var t = function (t, r, n) {
                e.nextTick((function () {
                    t(r, n)
                }))
            };

            function r() {
            }

            function n(e, t) {
                e.apply(null, t)
            }

            e.nextTick((function (r) {
                42 === r && (t = e.nextTick)
            }), 42), ci = function (e) {
                var i = function (r) {
                    var o = [r];
                    i = function (e) {
                        o.push(e)
                    }, e((function (e) {
                        var r = arguments;
                        for (i = function (e) {
                            return "[object Error]" === Object.prototype.toString.call(e)
                        }(e) ? s : a; o.length;) a(o.shift());

                        function a(e) {
                            t(n, e, r)
                        }
                    }))
                };
                return function (e) {
                    i(e || r)
                };

                function s(r) {
                    var o = [r];
                    i = function (e) {
                        o.push(e)
                    }, e((function (e) {
                        var r = arguments;
                        for (i = function (e) {
                            return "[object Error]" === Object.prototype.toString.call(e)
                        }(e) ? s : a; o.length;) a(o.shift());

                        function a(e) {
                            t(n, e, r)
                        }
                    }))
                }
            }
        }).call(this)
    }).call(this, _e);
    var di = {};
    (function (e) {
        (function () {
            di = function (t, r) {
                if ("undefined" == typeof Blob || !(t instanceof Blob)) throw new Error("first argument must be a Blob");
                if ("function" != typeof r) throw new Error("second argument must be a function");
                const n = new FileReader;
                n.addEventListener("loadend", (function t(i) {
                    n.removeEventListener("loadend", t, !1), i.error ? r(i.error) : r(null, e.from(n.result))
                }), !1), n.readAsArrayBuffer(t)
            }
        }).call(this)
    }).call(this, y({}).Buffer);
    var li = {};
    (function (e) {
        (function () {
            "use strict";
            var t = [255, 255, 26, 27, 28, 29, 30, 31, 255, 255, 255, 255, 255, 255, 255, 255, 255, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 255, 255, 255, 255, 255, 255, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 255, 255, 255, 255, 255];
            li.decode = function (r) {
                var n, i = 0, s = 0, o = 0;
                e.isBuffer(r) || (r = new e(r));
                for (var a = new e(Math.ceil(5 * r.length / 8)), h = 0; h < r.length && 61 !== r[h]; h++) {
                    var u = r[h] - 48;
                    if (!(u < t.length)) throw new Error("Invalid input - it is not base32 encoded string");
                    s = t[u], i <= 3 ? 0 == (i = (i + 5) % 8) ? (n |= s, a[o] = n, o++, n = 0) : n |= 255 & s << 8 - i : (n |= 255 & s >>> (i = (i + 5) % 8), a[o] = n, o++, n = 255 & s << 8 - i)
                }
                return a.slice(0, o)
            }
        }).call(this)
    }).call(this, y({}).Buffer);
    var fi = {};
    li.encode, fi.decode = li.decode;
    var pi = {};

    function mi(e) {
        return e.reduce((e, t, r, n) => {
            const i = t.split("-").map(e => parseInt(e));
            return e.concat(((e, t = e) => Array.from({length: t - e + 1}, (t, r) => r + e))(...i))
        }, [])
    }

    (pi = mi).parse = mi, pi.compose = function (e) {
        return e.reduce((e, t, r, n) => (0 !== r && t === n[r - 1] + 1 || e.push([]), e[e.length - 1].push(t), e), []).map(e => e.length > 1 ? `${e[0]}-${e[e.length - 1]}` : "" + e[0])
    };
    var gi = {};
    (function (e) {
        (function () {
            function t(t) {
                const r = {}, n = t.split("magnet:?")[1];
                let i;
                return (n && n.length >= 0 ? n.split("&") : []).forEach(e => {
                    const t = e.split("=");
                    if (2 !== t.length) return;
                    const n = t[0];
                    let i = t[1];
                    "dn" === n && (i = decodeURIComponent(i).replace(/\+/g, " ")), "tr" !== n && "xs" !== n && "as" !== n && "ws" !== n || (i = decodeURIComponent(i)), "kt" === n && (i = decodeURIComponent(i).split("+")), "ix" === n && (i = Number(i)), "so" === n && (i = pi.parse(decodeURIComponent(i).split(","))), r[n] ? (Array.isArray(r[n]) || (r[n] = [r[n]]), r[n].push(i)) : r[n] = i
                }), r.xt && (Array.isArray(r.xt) ? r.xt : [r.xt]).forEach(t => {
                    if (i = t.match(/^urn:btih:(.{40})/)) r.infoHash = i[1].toLowerCase(); else if (i = t.match(/^urn:btih:(.{32})/)) {
                        const t = fi.decode(i[1]);
                        r.infoHash = e.from(t, "binary").toString("hex")
                    }
                }), r.xs && (Array.isArray(r.xs) ? r.xs : [r.xs]).forEach(e => {
                    (i = e.match(/^urn:btpk:(.{64})/)) && (r.publicKey = i[1].toLowerCase())
                }), r.infoHash && (r.infoHashBuffer = e.from(r.infoHash, "hex")), r.publicKey && (r.publicKeyBuffer = e.from(r.publicKey, "hex")), r.dn && (r.name = r.dn), r.kt && (r.keywords = r.kt), r.announce = [], ("string" == typeof r.tr || Array.isArray(r.tr)) && (r.announce = r.announce.concat(r.tr)), r.urlList = [], ("string" == typeof r.as || Array.isArray(r.as)) && (r.urlList = r.urlList.concat(r.as)), ("string" == typeof r.ws || Array.isArray(r.ws)) && (r.urlList = r.urlList.concat(r.ws)), r.peerAddresses = [], ("string" == typeof r["x.pe"] || Array.isArray(r["x.pe"])) && (r.peerAddresses = r.peerAddresses.concat(r["x.pe"])), r.announce = Array.from(new Set(r.announce)), r.urlList = Array.from(new Set(r.urlList)), r.peerAddresses = Array.from(new Set(r.peerAddresses)), r
            }

            (gi = t).decode = t, gi.encode = function (e) {
                (e = Object.assign({}, e)).infoHashBuffer && (e.xt = "urn:btih:" + e.infoHashBuffer.toString("hex")), e.infoHash && (e.xt = "urn:btih:" + e.infoHash), e.publicKeyBuffer && (e.xs = "urn:btpk:" + e.publicKeyBuffer.toString("hex")), e.publicKey && (e.xs = "urn:btpk:" + e.publicKey), e.name && (e.dn = e.name), e.keywords && (e.kt = e.keywords), e.announce && (e.tr = e.announce), e.urlList && (e.ws = e.urlList, delete e.as), e.peerAddresses && (e["x.pe"] = e.peerAddresses);
                let t = "magnet:?";
                return Object.keys(e).filter(e => 2 === e.length || "x.pe" === e).forEach((r, n) => {
                    const i = Array.isArray(e[r]) ? e[r] : [e[r]];
                    i.forEach((e, i) => {
                        (n > 0 || i > 0) && ("kt" !== r && "so" !== r || 0 === i) && (t += "&"), "dn" === r && (e = encodeURIComponent(e).replace(/%20/g, "+")), "tr" !== r && "as" !== r && "ws" !== r || (e = encodeURIComponent(e)), "xs" !== r || e.startsWith("urn:btpk:") || (e = encodeURIComponent(e)), "kt" === r && (e = encodeURIComponent(e)), "so" !== r && (t += "kt" === r && i > 0 ? "+" + e : `${r}=${e}`)
                    }), "so" === r && (t += `${r}=${pi.compose(i)}`)
                }), t
            }
        }).call(this)
    }).call(this, y({}).Buffer);
    var _i = {};
    (function (e, t) {
        (function () {
            function r(e) {
                if ("string" == typeof e && /^(stream-)?magnet:/.test(e)) {
                    const t = gi(e);
                    if (!t.infoHash) throw new Error("Invalid torrent identifier");
                    return t
                }
                if ("string" == typeof e && (/^[a-f0-9]{40}$/i.test(e) || /^[a-z2-7]{32}$/i.test(e))) return gi("magnet:?xt=urn:btih:" + e);
                if (t.isBuffer(e) && 20 === e.length) return gi("magnet:?xt=urn:btih:" + e.toString("hex"));
                if (t.isBuffer(e)) return function (e) {
                    t.isBuffer(e) && (e = q.decode(e)), i(e.info, "info"), i(e.info["name.utf-8"] || e.info.name, "info.name"), i(e.info["piece length"], "info['piece length']"), i(e.info.pieces, "info.pieces"), e.info.files ? e.info.files.forEach(e => {
                        i("number" == typeof e.length, "info.files[0].length"), i(e["path.utf-8"] || e.path, "info.files[0].path")
                    }) : i("number" == typeof e.info.length, "info.length");
                    const r = {
                        info: e.info,
                        infoBuffer: q.encode(e.info),
                        name: (e.info["name.utf-8"] || e.info.name).toString(),
                        announce: []
                    };
                    r.infoHash = Wt.sync(r.infoBuffer), r.infoHashBuffer = t.from(r.infoHash, "hex"), void 0 !== e.info.private && (r.private = !!e.info.private), e["creation date"] && (r.created = new Date(1e3 * e["creation date"])), e["created by"] && (r.createdBy = e["created by"].toString()), t.isBuffer(e.comment) && (r.comment = e.comment.toString()), Array.isArray(e["announce-list"]) && e["announce-list"].length > 0 ? e["announce-list"].forEach(e => {
                        e.forEach(e => {
                            r.announce.push(e.toString())
                        })
                    }) : e.announce && r.announce.push(e.announce.toString()), t.isBuffer(e["url-list"]) && (e["url-list"] = e["url-list"].length > 0 ? [e["url-list"]] : []), r.urlList = (e["url-list"] || []).map(e => e.toString()), r.announce = Array.from(new Set(r.announce)), r.urlList = Array.from(new Set(r.urlList));
                    const s = e.info.files || [e.info];
                    r.files = s.map((e, t) => {
                        const i = [].concat(r.name, e["path.utf-8"] || e.path || []).map(e => e.toString());
                        return {
                            path: ft.join.apply(null, [ft.sep].concat(i)).slice(1),
                            name: i[i.length - 1],
                            length: e.length,
                            offset: s.slice(0, t).reduce(n, 0)
                        }
                    }), r.length = s.reduce(n, 0);
                    const o = r.files[r.files.length - 1];
                    return r.pieceLength = e.info["piece length"], r.lastPieceLength = (o.offset + o.length) % r.pieceLength || r.pieceLength, r.pieces = function (e) {
                        const t = [];
                        for (let r = 0; r < e.length; r += 20) t.push(e.slice(r, r + 20).toString("hex"));
                        return t
                    }(e.info.pieces), r
                }(e);
                if (e && e.infoHash) return e.infoHash = e.infoHash.toLowerCase(), e.announce || (e.announce = []), "string" == typeof e.announce && (e.announce = [e.announce]), e.urlList || (e.urlList = []), e;
                throw new Error("Invalid torrent identifier")
            }

            function n(e, t) {
                return e + t.length
            }

            function i(e, t) {
                if (!e) throw new Error("Torrent is missing required field: " + t)
            }

            (_i = r).remote = function t(n, i, s) {
                if ("function" == typeof i) return t(n, {}, i);
                if ("function" != typeof s) throw new Error("second argument must be a Function");
                let o;
                try {
                    o = r(n)
                } catch (Ga) {
                }

                function a(e) {
                    try {
                        o = r(e)
                    } catch (Ga) {
                        return s(Ga)
                    }
                    o && o.infoHash ? s(null, o) : s(new Error("Invalid torrent identifier"))
                }

                o && o.infoHash ? e.nextTick(() => {
                    s(null, o)
                }) : "undefined" != typeof Blob && n instanceof Blob ? di(n, (e, t) => {
                    if (e) return s(new Error("Error converting Blob: " + e.message));
                    a(t)
                }) : "function" == typeof Nr && /^https?:/.test(n) ? (i = Object.assign({
                    url: n,
                    timeout: 3e4,
                    headers: {"user-agent": "WebTorrent (https://webtorrent.io)"}
                }, i), Nr.concat(i, (e, t, r) => {
                    if (e) return s(new Error("Error downloading torrent: " + e.message));
                    a(r)
                })) : "function" == typeof St.readFile && "string" == typeof n ? St.readFile(n, (e, t) => {
                    if (e) return s(new Error("Invalid torrent identifier"));
                    a(t)
                }) : e.nextTick(() => {
                    s(new Error("Invalid torrent identifier"))
                })
            }, _i.toMagnetURI = gi.encode, _i.toTorrentFile = function (e) {
                const r = {info: e.info};
                return r["announce-list"] = (e.announce || []).map(e => (r.announce || (r.announce = e), [e = t.from(e, "utf8")])), r["url-list"] = e.urlList || [], void 0 !== e.private && (r.private = Number(e.private)), e.created && (r["creation date"] = e.created.getTime() / 1e3 | 0), e.createdBy && (r["created by"] = e.createdBy), e.comment && (r.comment = e.comment), q.encode(r)
            }, t.alloc(0)
        }).call(this)
    }).call(this, _e, y({}).Buffer);
    var yi = function () {
        if ("undefined" == typeof globalThis) return null;
        var e = {
            RTCPeerConnection: globalThis.RTCPeerConnection || globalThis.mozRTCPeerConnection || globalThis.webkitRTCPeerConnection,
            RTCSessionDescription: globalThis.RTCSessionDescription || globalThis.mozRTCSessionDescription || globalThis.webkitRTCSessionDescription,
            RTCIceCandidate: globalThis.RTCIceCandidate || globalThis.mozRTCIceCandidate || globalThis.webkitRTCIceCandidate
        };
        return e.RTCPeerConnection ? e : null
    }, bi = {};
    let vi;
    (function (e, t) {
        (function () {
            "use strict";
            var r = I.Buffer, n = t.crypto || t.msCrypto;
            bi = n && n.getRandomValues ? function (t, i) {
                if (t > 4294967295) throw new RangeError("requested too many random bytes");
                var s = r.allocUnsafe(t);
                if (t > 0) if (t > 65536) for (var o = 0; o < t; o += 65536) n.getRandomValues(s.slice(o, o + 65536)); else n.getRandomValues(s);
                return "function" == typeof i ? e.nextTick((function () {
                    i(null, s)
                })) : s
            } : function () {
                throw new Error("Secure random number generation is not supported by this browser.\nUse Chrome, Firefox or Internet Explorer 11")
            }
        }).call(this)
    }).call(this, _e, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {});
    var wi = "function" == typeof queueMicrotask ? queueMicrotask.bind(globalThis) : e => (vi || (vi = Promise.resolve())).then(e).catch(e => setTimeout(() => {
        throw e
    }, 0));

    function Ei(e, t) {
        for (const r in t) Object.defineProperty(e, r, {value: t[r], enumerable: !0, configurable: !0});
        return e
    }

    var ki = function (e, t, r) {
        if (!e || "string" == typeof e) throw new TypeError("Please pass an Error to err-code");
        r || (r = {}), "object" == typeof t && (r = t, t = void 0), null != t && (r.code = t);
        try {
            return Ei(e, r)
        } catch (n) {
            r.message = e.message, r.stack = e.stack;
            const t = function () {
            };
            return t.prototype = Object.create(Object.getPrototypeOf(e)), Ei(new t, r)
        }
    }, Si = {};
    const Ci = Xt("simple-peer"), {Buffer: xi} = y({});

    function Ai(e) {
        return e.replace(/a=ice-options:trickle\s\n/g, "")
    }

    class Ti extends dt.Duplex {
        constructor(e) {
            if (super(e = Object.assign({allowHalfOpen: !1}, e)), this._id = bi(4).toString("hex").slice(0, 7), this._debug("new peer %o", e), this.channelName = e.initiator ? e.channelName || bi(20).toString("hex") : null, this.initiator = e.initiator || !1, this.channelConfig = e.channelConfig || Ti.channelConfig, this.channelNegotiated = this.channelConfig.negotiated, this.config = Object.assign({}, Ti.config, e.config), this.offerOptions = e.offerOptions || {}, this.answerOptions = e.answerOptions || {}, this.sdpTransform = e.sdpTransform || (e => e), this.streams = e.streams || (e.stream ? [e.stream] : []), this.trickle = void 0 === e.trickle || e.trickle, this.allowHalfTrickle = void 0 !== e.allowHalfTrickle && e.allowHalfTrickle, this.iceCompleteTimeout = e.iceCompleteTimeout || 5e3, this.destroyed = !1, this.destroying = !1, this._connected = !1, this.remoteAddress = void 0, this.remoteFamily = void 0, this.remotePort = void 0, this.localAddress = void 0, this.localFamily = void 0, this.localPort = void 0, this._wrtc = e.wrtc && "object" == typeof e.wrtc ? e.wrtc : yi(), !this._wrtc) throw"undefined" == typeof window ? ki(new Error("No WebRTC support: Specify `opts.wrtc` option in this environment"), "ERR_WEBRTC_SUPPORT") : ki(new Error("No WebRTC support: Not a supported browser"), "ERR_WEBRTC_SUPPORT");
            this._pcReady = !1, this._channelReady = !1, this._iceComplete = !1, this._iceCompleteTimer = null, this._channel = null, this._pendingCandidates = [], this._isNegotiating = !1, this._firstNegotiation = !0, this._batchedNegotiation = !1, this._queuedNegotiation = !1, this._sendersAwaitingStable = [], this._senderMap = new Map, this._closingInterval = null, this._remoteTracks = [], this._remoteStreams = [], this._chunk = null, this._cb = null, this._interval = null;
            try {
                this._pc = new this._wrtc.RTCPeerConnection(this.config)
            } catch (Ga) {
                return void wi(() => this.destroy(ki(Ga, "ERR_PC_CONSTRUCTOR")))
            }
            this._isReactNativeWebrtc = "number" == typeof this._pc._peerConnectionId, this._pc.oniceconnectionstatechange = () => {
                this._onIceStateChange()
            }, this._pc.onicegatheringstatechange = () => {
                this._onIceStateChange()
            }, this._pc.onconnectionstatechange = () => {
                this._onConnectionStateChange()
            }, this._pc.onsignalingstatechange = () => {
                this._onSignalingStateChange()
            }, this._pc.onicecandidate = e => {
                this._onIceCandidate(e)
            }, this.initiator || this.channelNegotiated ? this._setupData({channel: this._pc.createDataChannel(this.channelName, this.channelConfig)}) : this._pc.ondatachannel = e => {
                this._setupData(e)
            }, this.streams && this.streams.forEach(e => {
                this.addStream(e)
            }), this._pc.ontrack = e => {
                this._onTrack(e)
            }, this._debug("initial negotiation"), this._needsNegotiation(), this._onFinishBound = () => {
                this._onFinish()
            }, this.once("finish", this._onFinishBound)
        }

        get bufferSize() {
            return this._channel && this._channel.bufferedAmount || 0
        }

        get connected() {
            return this._connected && "open" === this._channel.readyState
        }

        address() {
            return {port: this.localPort, family: this.localFamily, address: this.localAddress}
        }

        signal(e) {
            if (this.destroyed) throw ki(new Error("cannot signal after peer is destroyed"), "ERR_SIGNALING");
            if ("string" == typeof e) try {
                e = JSON.parse(e)
            } catch (Ga) {
                e = {}
            }
            this._debug("signal()"), e.renegotiate && this.initiator && (this._debug("got request to renegotiate"), this._needsNegotiation()), e.transceiverRequest && this.initiator && (this._debug("got request for transceiver"), this.addTransceiver(e.transceiverRequest.kind, e.transceiverRequest.init)), e.candidate && (this._pc.remoteDescription && this._pc.remoteDescription.type ? this._addIceCandidate(e.candidate) : this._pendingCandidates.push(e.candidate)), e.sdp && this._pc.setRemoteDescription(new this._wrtc.RTCSessionDescription(e)).then(() => {
                this.destroyed || (this._pendingCandidates.forEach(e => {
                    this._addIceCandidate(e)
                }), this._pendingCandidates = [], "offer" === this._pc.remoteDescription.type && this._createAnswer())
            }).catch(e => {
                this.destroy(ki(e, "ERR_SET_REMOTE_DESCRIPTION"))
            }), e.sdp || e.candidate || e.renegotiate || e.transceiverRequest || this.destroy(ki(new Error("signal() called with invalid signal data"), "ERR_SIGNALING"))
        }

        _addIceCandidate(e) {
            const t = new this._wrtc.RTCIceCandidate(e);
            this._pc.addIceCandidate(t).catch(e => {
                !t.address || t.address.endsWith(".local") ? console.warn("Ignoring unsupported ICE candidate.") : this.destroy(ki(e, "ERR_ADD_ICE_CANDIDATE"))
            })
        }

        send(e) {
            this._channel.send(e)
        }

        addTransceiver(e, t) {
            if (this._debug("addTransceiver()"), this.initiator) try {
                this._pc.addTransceiver(e, t), this._needsNegotiation()
            } catch (Ga) {
                this.destroy(ki(Ga, "ERR_ADD_TRANSCEIVER"))
            } else this.emit("signal", {type: "transceiverRequest", transceiverRequest: {kind: e, init: t}})
        }

        addStream(e) {
            this._debug("addStream()"), e.getTracks().forEach(t => {
                this.addTrack(t, e)
            })
        }

        addTrack(e, t) {
            this._debug("addTrack()");
            const r = this._senderMap.get(e) || new Map;
            let n = r.get(t);
            if (n) throw n.removed ? ki(new Error("Track has been removed. You should enable/disable tracks that you want to re-add."), "ERR_SENDER_REMOVED") : ki(new Error("Track has already been added to that stream."), "ERR_SENDER_ALREADY_ADDED");
            n = this._pc.addTrack(e, t), r.set(t, n), this._senderMap.set(e, r), this._needsNegotiation()
        }

        replaceTrack(e, t, r) {
            this._debug("replaceTrack()");
            const n = this._senderMap.get(e), i = n ? n.get(r) : null;
            if (!i) throw ki(new Error("Cannot replace track that was never added."), "ERR_TRACK_NOT_ADDED");
            t && this._senderMap.set(t, n), null != i.replaceTrack ? i.replaceTrack(t) : this.destroy(ki(new Error("replaceTrack is not supported in this browser"), "ERR_UNSUPPORTED_REPLACETRACK"))
        }

        removeTrack(e, t) {
            this._debug("removeSender()");
            const r = this._senderMap.get(e), n = r ? r.get(t) : null;
            if (!n) throw ki(new Error("Cannot remove track that was never added."), "ERR_TRACK_NOT_ADDED");
            try {
                n.removed = !0, this._pc.removeTrack(n)
            } catch (Ga) {
                "NS_ERROR_UNEXPECTED" === Ga.name ? this._sendersAwaitingStable.push(n) : this.destroy(ki(Ga, "ERR_REMOVE_TRACK"))
            }
            this._needsNegotiation()
        }

        removeStream(e) {
            this._debug("removeSenders()"), e.getTracks().forEach(t => {
                this.removeTrack(t, e)
            })
        }

        _needsNegotiation() {
            this._debug("_needsNegotiation"), this._batchedNegotiation || (this._batchedNegotiation = !0, wi(() => {
                this._batchedNegotiation = !1, this.initiator || !this._firstNegotiation ? (this._debug("starting batched negotiation"), this.negotiate()) : this._debug("non-initiator initial negotiation request discarded"), this._firstNegotiation = !1
            }))
        }

        negotiate() {
            this.initiator ? this._isNegotiating ? (this._queuedNegotiation = !0, this._debug("already negotiating, queueing")) : (this._debug("start negotiation"), setTimeout(() => {
                this._createOffer()
            }, 0)) : this._isNegotiating ? (this._queuedNegotiation = !0, this._debug("already negotiating, queueing")) : (this._debug("requesting negotiation from initiator"), this.emit("signal", {
                type: "renegotiate",
                renegotiate: !0
            })), this._isNegotiating = !0
        }

        destroy(e) {
            this._destroy(e, () => {
            })
        }

        _destroy(e, t) {
            this.destroyed || this.destroying || (this.destroying = !0, this._debug("destroying (error: %s)", e && (e.message || e)), wi(() => {
                if (this.destroyed = !0, this.destroying = !1, this._debug("destroy (error: %s)", e && (e.message || e)), this.readable = this.writable = !1, this._readableState.ended || this.push(null), this._writableState.finished || this.end(), this._connected = !1, this._pcReady = !1, this._channelReady = !1, this._remoteTracks = null, this._remoteStreams = null, this._senderMap = null, clearInterval(this._closingInterval), this._closingInterval = null, clearInterval(this._interval), this._interval = null, this._chunk = null, this._cb = null, this._onFinishBound && this.removeListener("finish", this._onFinishBound), this._onFinishBound = null, this._channel) {
                    try {
                        this._channel.close()
                    } catch (e) {
                    }
                    this._channel.onmessage = null, this._channel.onopen = null, this._channel.onclose = null, this._channel.onerror = null
                }
                if (this._pc) {
                    try {
                        this._pc.close()
                    } catch (e) {
                    }
                    this._pc.oniceconnectionstatechange = null, this._pc.onicegatheringstatechange = null, this._pc.onsignalingstatechange = null, this._pc.onicecandidate = null, this._pc.ontrack = null, this._pc.ondatachannel = null
                }
                this._pc = null, this._channel = null, e && this.emit("error", e), this.emit("close"), t()
            }))
        }

        _setupData(e) {
            if (!e.channel) return this.destroy(ki(new Error("Data channel event is missing `channel` property"), "ERR_DATA_CHANNEL"));
            this._channel = e.channel, this._channel.binaryType = "arraybuffer", "number" == typeof this._channel.bufferedAmountLowThreshold && (this._channel.bufferedAmountLowThreshold = 65536), this.channelName = this._channel.label, this._channel.onmessage = e => {
                this._onChannelMessage(e)
            }, this._channel.onbufferedamountlow = () => {
                this._onChannelBufferedAmountLow()
            }, this._channel.onopen = () => {
                this._onChannelOpen()
            }, this._channel.onclose = () => {
                this._onChannelClose()
            }, this._channel.onerror = e => {
                this.destroy(ki(e, "ERR_DATA_CHANNEL"))
            };
            let t = !1;
            this._closingInterval = setInterval(() => {
                this._channel && "closing" === this._channel.readyState ? (t && this._onChannelClose(), t = !0) : t = !1
            }, 5e3)
        }

        _read() {
        }

        _write(e, t, r) {
            if (this.destroyed) return r(ki(new Error("cannot write after peer is destroyed"), "ERR_DATA_CHANNEL"));
            if (this._connected) {
                try {
                    this.send(e)
                } catch (Ga) {
                    return this.destroy(ki(Ga, "ERR_DATA_CHANNEL"))
                }
                this._channel.bufferedAmount > 65536 ? (this._debug("start backpressure: bufferedAmount %d", this._channel.bufferedAmount), this._cb = r) : r(null)
            } else this._debug("write before connect"), this._chunk = e, this._cb = r
        }

        _onFinish() {
            if (this.destroyed) return;
            const e = () => {
                setTimeout(() => this.destroy(), 1e3)
            };
            this._connected ? e() : this.once("connect", e)
        }

        _startIceCompleteTimeout() {
            this.destroyed || this._iceCompleteTimer || (this._debug("started iceComplete timeout"), this._iceCompleteTimer = setTimeout(() => {
                this._iceComplete || (this._iceComplete = !0, this._debug("iceComplete timeout completed"), this.emit("iceTimeout"), this.emit("_iceComplete"))
            }, this.iceCompleteTimeout))
        }

        _createOffer() {
            this.destroyed || this._pc.createOffer(this.offerOptions).then(e => {
                if (this.destroyed) return;
                this.trickle || this.allowHalfTrickle || (e.sdp = Ai(e.sdp)), e.sdp = this.sdpTransform(e.sdp);
                const t = () => {
                    if (this.destroyed) return;
                    const t = this._pc.localDescription || e;
                    this._debug("signal"), this.emit("signal", {type: t.type, sdp: t.sdp})
                };
                this._pc.setLocalDescription(e).then(() => {
                    this._debug("createOffer success"), this.destroyed || (this.trickle || this._iceComplete ? t() : this.once("_iceComplete", t))
                }).catch(e => {
                    this.destroy(ki(e, "ERR_SET_LOCAL_DESCRIPTION"))
                })
            }).catch(e => {
                this.destroy(ki(e, "ERR_CREATE_OFFER"))
            })
        }

        _requestMissingTransceivers() {
            this._pc.getTransceivers && this._pc.getTransceivers().forEach(e => {
                e.mid || !e.sender.track || e.requested || (e.requested = !0, this.addTransceiver(e.sender.track.kind))
            })
        }

        _createAnswer() {
            this.destroyed || this._pc.createAnswer(this.answerOptions).then(e => {
                if (this.destroyed) return;
                this.trickle || this.allowHalfTrickle || (e.sdp = Ai(e.sdp)), e.sdp = this.sdpTransform(e.sdp);
                const t = () => {
                    if (this.destroyed) return;
                    const t = this._pc.localDescription || e;
                    this._debug("signal"), this.emit("signal", {
                        type: t.type,
                        sdp: t.sdp
                    }), this.initiator || this._requestMissingTransceivers()
                };
                this._pc.setLocalDescription(e).then(() => {
                    this.destroyed || (this.trickle || this._iceComplete ? t() : this.once("_iceComplete", t))
                }).catch(e => {
                    this.destroy(ki(e, "ERR_SET_LOCAL_DESCRIPTION"))
                })
            }).catch(e => {
                this.destroy(ki(e, "ERR_CREATE_ANSWER"))
            })
        }

        _onConnectionStateChange() {
            this.destroyed || "failed" === this._pc.connectionState && this.destroy(ki(new Error("Connection failed."), "ERR_CONNECTION_FAILURE"))
        }

        _onIceStateChange() {
            if (this.destroyed) return;
            const e = this._pc.iceConnectionState, t = this._pc.iceGatheringState;
            this._debug("iceStateChange (connection: %s) (gathering: %s)", e, t), this.emit("iceStateChange", e, t), "connected" !== e && "completed" !== e || (this._pcReady = !0, this._maybeReady()), "failed" === e && this.destroy(ki(new Error("Ice connection failed."), "ERR_ICE_CONNECTION_FAILURE")), "closed" === e && this.destroy(ki(new Error("Ice connection closed."), "ERR_ICE_CONNECTION_CLOSED"))
        }

        getStats(e) {
            const t = e => ("[object Array]" === Object.prototype.toString.call(e.values) && e.values.forEach(t => {
                Object.assign(e, t)
            }), e);
            0 === this._pc.getStats.length || this._isReactNativeWebrtc ? this._pc.getStats().then(r => {
                const n = [];
                r.forEach(e => {
                    n.push(t(e))
                }), e(null, n)
            }, t => e(t)) : this._pc.getStats.length > 0 ? this._pc.getStats(r => {
                if (this.destroyed) return;
                const n = [];
                r.result().forEach(e => {
                    const r = {};
                    e.names().forEach(t => {
                        r[t] = e.stat(t)
                    }), r.id = e.id, r.type = e.type, r.timestamp = e.timestamp, n.push(t(r))
                }), e(null, n)
            }, t => e(t)) : e(null, [])
        }

        _maybeReady() {
            if (this._debug("maybeReady pc %s channel %s", this._pcReady, this._channelReady), this._connected || this._connecting || !this._pcReady || !this._channelReady) return;
            this._connecting = !0;
            const e = () => {
                this.destroyed || this.getStats((t, r) => {
                    if (this.destroyed) return;
                    t && (r = []);
                    const n = {}, i = {}, s = {};
                    let o = !1;
                    r.forEach(e => {
                        "remotecandidate" !== e.type && "remote-candidate" !== e.type || (n[e.id] = e), "localcandidate" !== e.type && "local-candidate" !== e.type || (i[e.id] = e), "candidatepair" !== e.type && "candidate-pair" !== e.type || (s[e.id] = e)
                    });
                    const a = e => {
                        o = !0;
                        let t = i[e.localCandidateId];
                        t && (t.ip || t.address) ? (this.localAddress = t.ip || t.address, this.localPort = Number(t.port)) : t && t.ipAddress ? (this.localAddress = t.ipAddress, this.localPort = Number(t.portNumber)) : "string" == typeof e.googLocalAddress && (t = e.googLocalAddress.split(":"), this.localAddress = t[0], this.localPort = Number(t[1])), this.localAddress && (this.localFamily = this.localAddress.includes(":") ? "IPv6" : "IPv4");
                        let r = n[e.remoteCandidateId];
                        r && (r.ip || r.address) ? (this.remoteAddress = r.ip || r.address, this.remotePort = Number(r.port)) : r && r.ipAddress ? (this.remoteAddress = r.ipAddress, this.remotePort = Number(r.portNumber)) : "string" == typeof e.googRemoteAddress && (r = e.googRemoteAddress.split(":"), this.remoteAddress = r[0], this.remotePort = Number(r[1])), this.remoteAddress && (this.remoteFamily = this.remoteAddress.includes(":") ? "IPv6" : "IPv4"), this._debug("connect local: %s:%s remote: %s:%s", this.localAddress, this.localPort, this.remoteAddress, this.remotePort)
                    };
                    if (r.forEach(e => {
                        "transport" === e.type && e.selectedCandidatePairId && a(s[e.selectedCandidatePairId]), ("googCandidatePair" === e.type && "true" === e.googActiveConnection || ("candidatepair" === e.type || "candidate-pair" === e.type) && e.selected) && a(e)
                    }), o || Object.keys(s).length && !Object.keys(i).length) {
                        if (this._connecting = !1, this._connected = !0, this._chunk) {
                            try {
                                this.send(this._chunk)
                            } catch (t) {
                                return this.destroy(ki(t, "ERR_DATA_CHANNEL"))
                            }
                            this._chunk = null, this._debug('sent chunk from "write before connect"');
                            const e = this._cb;
                            this._cb = null, e(null)
                        }
                        "number" != typeof this._channel.bufferedAmountLowThreshold && (this._interval = setInterval(() => this._onInterval(), 150), this._interval.unref && this._interval.unref()), this._debug("connect"), this.emit("connect")
                    } else setTimeout(e, 100)
                })
            };
            e()
        }

        _onInterval() {
            !this._cb || !this._channel || this._channel.bufferedAmount > 65536 || this._onChannelBufferedAmountLow()
        }

        _onSignalingStateChange() {
            this.destroyed || ("stable" === this._pc.signalingState && (this._isNegotiating = !1, this._debug("flushing sender queue", this._sendersAwaitingStable), this._sendersAwaitingStable.forEach(e => {
                this._pc.removeTrack(e), this._queuedNegotiation = !0
            }), this._sendersAwaitingStable = [], this._queuedNegotiation ? (this._debug("flushing negotiation queue"), this._queuedNegotiation = !1, this._needsNegotiation()) : (this._debug("negotiated"), this.emit("negotiated"))), this._debug("signalingStateChange %s", this._pc.signalingState), this.emit("signalingStateChange", this._pc.signalingState))
        }

        _onIceCandidate(e) {
            this.destroyed || (e.candidate && this.trickle ? this.emit("signal", {
                type: "candidate",
                candidate: {
                    candidate: e.candidate.candidate,
                    sdpMLineIndex: e.candidate.sdpMLineIndex,
                    sdpMid: e.candidate.sdpMid
                }
            }) : e.candidate || this._iceComplete || (this._iceComplete = !0, this.emit("_iceComplete")), e.candidate && this._startIceCompleteTimeout())
        }

        _onChannelMessage(e) {
            if (this.destroyed) return;
            let t = e.data;
            t instanceof ArrayBuffer && (t = xi.from(t)), this.push(t)
        }

        _onChannelBufferedAmountLow() {
            if (this.destroyed || !this._cb) return;
            this._debug("ending backpressure: bufferedAmount %d", this._channel.bufferedAmount);
            const e = this._cb;
            this._cb = null, e(null)
        }

        _onChannelOpen() {
            this._connected || this.destroyed || (this._debug("on channel open"), this._channelReady = !0, this._maybeReady())
        }

        _onChannelClose() {
            this.destroyed || (this._debug("on channel close"), this.destroy())
        }

        _onTrack(e) {
            this.destroyed || e.streams.forEach(t => {
                this._debug("on track"), this.emit("track", e.track, t), this._remoteTracks.push({
                    track: e.track,
                    stream: t
                }), this._remoteStreams.some(e => e.id === t.id) || (this._remoteStreams.push(t), wi(() => {
                    this._debug("on stream"), this.emit("stream", t)
                }))
            })
        }

        _debug() {
            const e = [].slice.call(arguments);
            e[0] = "[" + this._id + "] " + e[0], Ci.apply(null, e)
        }
    }

    Ti.WEBRTC_SUPPORT = !!yi(), Ti.config = {
        iceServers: [{urls: ["stun:stun.l.google.com:19302", "stun:global.stun.twilio.com:3478"]}],
        sdpSemantics: "unified-plan"
    }, Ti.channelConfig = {}, Si = Ti;
    var Ii, Ri = 1, Bi = function () {
        Ri = Ri + 1 & 65535
    }, Li = function (e) {
        Ii || (Ii = setInterval(Bi, 250)).unref && Ii.unref();
        var t = 4 * (e || 5), r = [0], n = 1, i = Ri - 1 & 65535;
        return function (e) {
            var s = Ri - i & 65535;
            for (s > t && (s = t), i = Ri; s--;) n === t && (n = 0), r[n] = r[0 === n ? t - 1 : n - 1], n++;
            e && (r[n - 1] += e);
            var o = r[n - 1], a = r.length < t ? 0 : r[n === t ? 0 : n];
            return r.length < 4 ? o : 4 * (o - a) / r.length
        }
    }, Oi = {};
    const Ui = /^\[?([^\]]+)\]?:(\d+)$/;
    let Pi = {}, Mi = 0;
    (Oi = function (e) {
        if (1e5 === Mi && Oi.reset(), !Pi[e]) {
            const t = Ui.exec(e);
            if (!t) throw new Error("invalid addr: " + e);
            Pi[e] = [t[1], Number(t[2])], Mi += 1
        }
        return Pi[e]
    }).reset = function () {
        Pi = {}, Mi = 0
    };
    var Di = {};

    function Ni(e) {
        var t = e >> 3;
        return e % 8 != 0 && t++, t
    }

    Object.defineProperty(Di, "__esModule", {value: !0});
    var ji = function () {
        function e(e, t) {
            void 0 === e && (e = 0);
            var r = null == t ? void 0 : t.grow;
            this.grow = r && isFinite(r) && Ni(r) || r || 0, this.buffer = "number" == typeof e ? new Uint8Array(Ni(e)) : e
        }

        return e.prototype.get = function (e) {
            var t = e >> 3;
            return t < this.buffer.length && !!(this.buffer[t] & 128 >> e % 8)
        }, e.prototype.set = function (e, t) {
            void 0 === t && (t = !0);
            var r = e >> 3;
            if (t) {
                if (this.buffer.length < r + 1) {
                    var n = Math.max(r + 1, Math.min(2 * this.buffer.length, this.grow));
                    if (n <= this.grow) {
                        var i = new Uint8Array(n);
                        i.set(this.buffer), this.buffer = i
                    }
                }
                this.buffer[r] |= 128 >> e % 8
            } else r < this.buffer.length && (this.buffer[r] &= ~(128 >> e % 8))
        }, e.prototype.forEach = function (e, t, r) {
            void 0 === t && (t = 0), void 0 === r && (r = 8 * this.buffer.length);
            for (var n = t, i = n >> 3, s = 128 >> n % 8, o = this.buffer[i]; n < r; n++) e(!!(o & s), n), s = 1 === s ? (o = this.buffer[++i], 128) : s >> 1
        }, e
    }();
    Di.default = ji;
    var Fi = class extends dt.Writable {
        constructor(e, t, r = {}) {
            if (super(r), !e || !e.put || !e.get) throw new Error("First argument must be an abstract-chunk-store compliant store");
            if (!(t = Number(t))) throw new Error("Second argument must be a chunk length");
            this._blockstream = new lt(t, {zeroPadding: !1}), this._outstandingPuts = 0;
            let n = 0;
            this._blockstream.on("data", t => {
                this.destroyed || (this._outstandingPuts += 1, e.put(n, t, () => {
                    this._outstandingPuts -= 1, 0 === this._outstandingPuts && "function" == typeof this._finalCb && (this._finalCb(null), this._finalCb = null)
                }), n += 1)
            }).on("error", e => {
                this.destroy(e)
            })
        }

        _write(e, t, r) {
            this._blockstream.write(e, t, r)
        }

        _final(e) {
            this._blockstream.end(), this._blockstream.once("end", () => {
                0 === this._outstandingPuts ? e(null) : this._finalCb = e
            })
        }

        destroy(e) {
            this.destroyed || (this.destroyed = !0, e && this.emit("error", e), this.emit("close"))
        }
    }, zi = {};
    (function (e) {
        (function () {
            zi.DEFAULT_ANNOUNCE_PEERS = 50, zi.binaryToHex = function (t) {
                return "string" != typeof t && (t = String(t)), e.from(t, "binary").toString("hex")
            }, zi.hexToBinary = function (t) {
                return "string" != typeof t && (t = String(t)), e.from(t, "hex").toString("binary")
            }, Object.assign(zi, ae)
        }).call(this)
    }).call(this, y({}).Buffer);
    var Hi = {};
    (function (e) {
        (function () {
            const t = Xt("simple-websocket"), r = "function" != typeof ae ? WebSocket : ae;

            class n extends dt.Duplex {
                constructor(e = {}) {
                    if ("string" == typeof e && (e = {url: e}), super(e = Object.assign({allowHalfOpen: !1}, e)), null == e.url && null == e.socket) throw new Error("Missing required `url` or `socket` option");
                    if (null != e.url && null != e.socket) throw new Error("Must specify either `url` or `socket` option, not both");
                    if (this._id = bi(4).toString("hex").slice(0, 7), this._debug("new websocket: %o", e), this.connected = !1, this.destroyed = !1, this._chunk = null, this._cb = null, this._interval = null, e.socket) this.url = e.socket.url, this._ws = e.socket, this.connected = e.socket.readyState === r.OPEN; else {
                        this.url = e.url;
                        try {
                            this._ws = "function" == typeof ae ? new r(e.url, e) : new r(e.url)
                        } catch (Ga) {
                            return void wi(() => this.destroy(Ga))
                        }
                    }
                    this._ws.binaryType = "arraybuffer", this._ws.onopen = () => {
                        this._onOpen()
                    }, this._ws.onmessage = e => {
                        this._onMessage(e)
                    }, this._ws.onclose = () => {
                        this._onClose()
                    }, this._ws.onerror = () => {
                        this.destroy(new Error("connection error to " + this.url))
                    }, this._onFinishBound = () => {
                        this._onFinish()
                    }, this.once("finish", this._onFinishBound)
                }

                send(e) {
                    this._ws.send(e)
                }

                destroy(e) {
                    this._destroy(e, () => {
                    })
                }

                _destroy(e, t) {
                    if (!this.destroyed) {
                        if (this._debug("destroy (error: %s)", e && (e.message || e)), this.readable = this.writable = !1, this._readableState.ended || this.push(null), this._writableState.finished || this.end(), this.connected = !1, this.destroyed = !0, clearInterval(this._interval), this._interval = null, this._chunk = null, this._cb = null, this._onFinishBound && this.removeListener("finish", this._onFinishBound), this._onFinishBound = null, this._ws) {
                            const t = this._ws, n = () => {
                                t.onclose = null
                            };
                            if (t.readyState === r.CLOSED) n(); else try {
                                t.onclose = n, t.close()
                            } catch (e) {
                                n()
                            }
                            t.onopen = null, t.onmessage = null, t.onerror = () => {
                            }
                        }
                        if (this._ws = null, e) {
                            if ("undefined" != typeof DOMException && e instanceof DOMException) {
                                const t = e.code;
                                (e = new Error(e.message)).code = t
                            }
                            this.emit("error", e)
                        }
                        this.emit("close"), t()
                    }
                }

                _read() {
                }

                _write(e, t, r) {
                    if (this.destroyed) return r(new Error("cannot write after socket is destroyed"));
                    if (this.connected) {
                        try {
                            this.send(e)
                        } catch (Ga) {
                            return this.destroy(Ga)
                        }
                        "function" != typeof ae && this._ws.bufferedAmount > 65536 ? (this._debug("start backpressure: bufferedAmount %d", this._ws.bufferedAmount), this._cb = r) : r(null)
                    } else this._debug("write before connect"), this._chunk = e, this._cb = r
                }

                _onFinish() {
                    if (this.destroyed) return;
                    const e = () => {
                        setTimeout(() => this.destroy(), 1e3)
                    };
                    this.connected ? e() : this.once("connect", e)
                }

                _onMessage(t) {
                    if (this.destroyed) return;
                    let r = t.data;
                    r instanceof ArrayBuffer && (r = e.from(r)), this.push(r)
                }

                _onOpen() {
                    if (!this.connected && !this.destroyed) {
                        if (this.connected = !0, this._chunk) {
                            try {
                                this.send(this._chunk)
                            } catch (Ga) {
                                return this.destroy(Ga)
                            }
                            this._chunk = null, this._debug('sent chunk from "write before connect"');
                            const e = this._cb;
                            this._cb = null, e(null)
                        }
                        "function" != typeof ae && (this._interval = setInterval(() => this._onInterval(), 150), this._interval.unref && this._interval.unref()), this._debug("connect"), this.emit("connect")
                    }
                }

                _onInterval() {
                    if (!this._cb || !this._ws || this._ws.bufferedAmount > 65536) return;
                    this._debug("ending backpressure: bufferedAmount %d", this._ws.bufferedAmount);
                    const e = this._cb;
                    this._cb = null, e(null)
                }

                _onClose() {
                    this.destroyed || (this._debug("on close"), this.destroy())
                }

                _debug() {
                    const e = [].slice.call(arguments);
                    e[0] = "[" + this._id + "] " + e[0], t.apply(null, e)
                }
            }

            n.WEBSOCKET_SUPPORT = !!r, Hi = n
        }).call(this)
    }).call(this, y({}).Buffer);
    var Wi = class extends ${constructor(e,t){super(),this.client=e,this.announceUrl=t,this.interval=null,this.destroyed=!1}setInterval(e) {
        null
    ==
        e
    &&(
        e = this.DEFAULT_ANNOUNCE_INTERVAL
    ),

        clearInterval(

        this
    .
        interval
    ),
        e
    &&(
        this
    .
        interval = setInterval(() => {
            this.announce(this.client._defaultAnnounceOpts())
        }, e)
    ,
        this
    .
        interval
    .
        unref
    &&
        this
    .
        interval
    .

        unref()

    )
    }
}, qi = {};
const Zi = Xt("bittorrent-tracker:websocket-tracker"), Vi = {};

class $i extends Wi {
    constructor(e, t, r) {
        super(e, t), Zi("new websocket tracker %s", t), this.peers = {}, this.socket = null, this.reconnecting = !1, this.retries = 0, this.reconnectTimer = null, this.expectingResponse = !1, this._openSocket()
    }

    announce(e) {
        if (this.destroyed || this.reconnecting) return;
        if (!this.socket.connected) return void this.socket.once("connect", () => {
            this.announce(e)
        });
        const t = Object.assign({}, e, {
            action: "announce",
            info_hash: this.client._infoHashBinary,
            peer_id: this.client._peerIdBinary
        });
        if (this._trackerId && (t.trackerid = this._trackerId), "stopped" === e.event || "completed" === e.event) this._send(t); else {
            const r = Math.min(e.numwant, 10);
            this._generateOffers(r, e => {
                t.numwant = r, t.offers = e, this._send(t)
            })
        }
    }

    scrape(e) {
        if (this.destroyed || this.reconnecting) return;
        if (!this.socket.connected) return void this.socket.once("connect", () => {
            this.scrape(e)
        });
        const t = {
            action: "scrape",
            info_hash: Array.isArray(e.infoHash) && e.infoHash.length > 0 ? e.infoHash.map(e => e.toString("binary")) : e.infoHash && e.infoHash.toString("binary") || this.client._infoHashBinary
        };
        this._send(t)
    }

    destroy(e = Ki) {
        if (this.destroyed) return e(null);
        this.destroyed = !0, clearInterval(this.interval), clearTimeout(this.reconnectTimer);
        for (const i in this.peers) {
            const e = this.peers[i];
            clearTimeout(e.trackerTimeout), e.destroy()
        }
        if (this.peers = null, this.socket && (this.socket.removeListener("connect", this._onSocketConnectBound), this.socket.removeListener("data", this._onSocketDataBound), this.socket.removeListener("close", this._onSocketCloseBound), this.socket.removeListener("error", this._onSocketErrorBound), this.socket = null), this._onSocketConnectBound = null, this._onSocketErrorBound = null, this._onSocketDataBound = null, this._onSocketCloseBound = null, Vi[this.announceUrl] && (Vi[this.announceUrl].consumers -= 1), Vi[this.announceUrl].consumers > 0) return e();
        let t = Vi[this.announceUrl];
        if (delete Vi[this.announceUrl], t.on("error", Ki), t.once("close", e), !this.expectingResponse) return n();
        var r = setTimeout(n, zi.DESTROY_TIMEOUT);

        function n() {
            r && (clearTimeout(r), r = null), t.removeListener("data", n), t.destroy(), t = null
        }

        t.once("data", n)
    }

    _openSocket() {
        this.destroyed = !1, this.peers || (this.peers = {}), this._onSocketConnectBound = () => {
            this._onSocketConnect()
        }, this._onSocketErrorBound = e => {
            this._onSocketError(e)
        }, this._onSocketDataBound = e => {
            this._onSocketData(e)
        }, this._onSocketCloseBound = () => {
            this._onSocketClose()
        }, this.socket = Vi[this.announceUrl], this.socket ? (Vi[this.announceUrl].consumers += 1, this.socket.connected && this._onSocketConnectBound()) : (this.socket = Vi[this.announceUrl] = new Hi(this.announceUrl), this.socket.consumers = 1, this.socket.once("connect", this._onSocketConnectBound)), this.socket.on("data", this._onSocketDataBound), this.socket.once("close", this._onSocketCloseBound), this.socket.once("error", this._onSocketErrorBound)
    }

    _onSocketConnect() {
        this.destroyed || this.reconnecting && (this.reconnecting = !1, this.retries = 0, this.announce(this.client._defaultAnnounceOpts()))
    }

    _onSocketData(e) {
        if (!this.destroyed) {
            this.expectingResponse = !1;
            try {
                e = JSON.parse(e)
            } catch (Ga) {
                return void this.client.emit("warning", new Error("Invalid tracker response"))
            }
            "announce" === e.action ? this._onAnnounceResponse(e) : "scrape" === e.action ? this._onScrapeResponse(e) : this._onSocketError(new Error("invalid action in WS response: " + e.action))
        }
    }

    _onAnnounceResponse(e) {
        if (e.info_hash !== this.client._infoHashBinary) return void Zi("ignoring websocket data from %s for %s (looking for %s: reused socket)", this.announceUrl, zi.binaryToHex(e.info_hash), this.client.infoHash);
        if (e.peer_id && e.peer_id === this.client._peerIdBinary) return;
        Zi("received %s from %s for %s", JSON.stringify(e), this.announceUrl, this.client.infoHash);
        const t = e["failure reason"];
        if (t) return this.client.emit("warning", new Error(t));
        const r = e["warning message"];
        r && this.client.emit("warning", new Error(r));
        const n = e.interval || e["min interval"];
        n && this.setInterval(1e3 * n);
        const i = e["tracker id"];
        if (i && (this._trackerId = i), null != e.complete) {
            const t = Object.assign({}, e, {announce: this.announceUrl, infoHash: zi.binaryToHex(e.info_hash)});
            this.client.emit("update", t)
        }
        let s;
        if (e.offer && e.peer_id && (Zi("creating peer (from remote offer)"), (s = this._createPeer()).id = zi.binaryToHex(e.peer_id), s.once("signal", t => {
            const r = {
                action: "announce",
                info_hash: this.client._infoHashBinary,
                peer_id: this.client._peerIdBinary,
                to_peer_id: e.peer_id,
                answer: t,
                offer_id: e.offer_id
            };
            this._trackerId && (r.trackerid = this._trackerId), this._send(r)
        }), s.signal(e.offer), this.client.emit("peer", s)), e.answer && e.peer_id) {
            const t = zi.binaryToHex(e.offer_id);
            (s = this.peers[t]) ? (s.id = zi.binaryToHex(e.peer_id), s.signal(e.answer), this.client.emit("peer", s), clearTimeout(s.trackerTimeout), s.trackerTimeout = null, delete this.peers[t]) : Zi("got unexpected answer: " + JSON.stringify(e.answer))
        }
    }

    _onScrapeResponse(e) {
        e = e.files || {};
        const t = Object.keys(e);
        0 !== t.length ? t.forEach(t => {
            const r = Object.assign(e[t], {announce: this.announceUrl, infoHash: zi.binaryToHex(t)});
            this.client.emit("scrape", r)
        }) : this.client.emit("warning", new Error("invalid scrape response"))
    }

    _onSocketClose() {
        this.destroyed || (this.destroy(), this._startReconnectTimer())
    }

    _onSocketError(e) {
        this.destroyed || (this.destroy(), this.client.emit("warning", e), this._startReconnectTimer())
    }

    _startReconnectTimer() {
        const e = Math.floor(3e5 * Math.random()) + Math.min(1e4 * Math.pow(2, this.retries), 36e5);
        this.reconnecting = !0, clearTimeout(this.reconnectTimer), this.reconnectTimer = setTimeout(() => {
            this.retries++, this._openSocket()
        }, e), this.reconnectTimer.unref && this.reconnectTimer.unref(), Zi("reconnecting socket in %s ms", e)
    }

    _send(e) {
        if (this.destroyed) return;
        this.expectingResponse = !0;
        const t = JSON.stringify(e);
        Zi("send %s", t), this.socket.send(t)
    }

    _generateOffers(e, t) {
        const r = this, n = [];
        Zi("generating %s offers", e);
        for (let o = 0; o < e; ++o) i();

        function i() {
            const e = bi(20).toString("hex");
            Zi("creating peer (from _generateOffers)");
            const t = r.peers[e] = r._createPeer({initiator: !0});
            t.once("signal", t => {
                n.push({offer: t, offer_id: zi.hexToBinary(e)}), s()
            }), t.trackerTimeout = setTimeout(() => {
                Zi("tracker timeout: destroying peer"), t.trackerTimeout = null, delete r.peers[e], t.destroy()
            }, 5e4), t.trackerTimeout.unref && t.trackerTimeout.unref()
        }

        function s() {
            n.length === e && (Zi("generated %s offers", e), t(n))
        }

        s()
    }

    _createPeer(e) {
        const t = this;
        e = Object.assign({trickle: !1, config: t.client._rtcConfig, wrtc: t.client._wrtc}, e);
        const r = new Si(e);
        return r.once("error", n), r.once("connect", (function e() {
            r.removeListener("error", n), r.removeListener("connect", e)
        })), r;

        function n(e) {
            t.client.emit("warning", new Error("Connection error: " + e.message)), r.destroy()
        }
    }
}

function Ki() {
}

$i.prototype.DEFAULT_ANNOUNCE_INTERVAL = 3e4, $i._socketPool = Vi, qi = $i;
var Gi = {};
(function (e, t) {
    (function () {
        const r = Xt("bittorrent-tracker:client");

        class n extends ${constructor(n={}
    )
    {
        if (super(), !n.peerId) throw new Error("Option `peerId` is required");
        if (!n.infoHash) throw new Error("Option `infoHash` is required");
        if (!n.announce) throw new Error("Option `announce` is required");
        if (!e.browser && !n.port) throw new Error("Option `port` is required");
        this.peerId = "string" == typeof n.peerId ? n.peerId : n.peerId.toString("hex"), this._peerIdBuffer = t.from(this.peerId, "hex"), this._peerIdBinary = this._peerIdBuffer.toString("binary"), this.infoHash = "string" == typeof n.infoHash ? n.infoHash.toLowerCase() : n.infoHash.toString("hex"), this._infoHashBuffer = t.from(this.infoHash, "hex"), this._infoHashBinary = this._infoHashBuffer.toString("binary"), r("new client %s", this.infoHash), this.destroyed = !1, this._port = n.port, this._getAnnounceOpts = n.getAnnounceOpts, this._rtcConfig = n.rtcConfig, this._userAgent = n.userAgent, this._wrtc = "function" == typeof n.wrtc ? n.wrtc() : n.wrtc;
        let i = "string" == typeof n.announce ? [n.announce] : null == n.announce ? [] : n.announce;
        i = i.map(e => ("/" === (e = e.toString())[e.length - 1] && (e = e.substring(0, e.length - 1)), e)), i = Array.from(new Set(i));
        const s = !1 !== this._wrtc && (!!this._wrtc || Si.WEBRTC_SUPPORT), o = t => {
            e.nextTick(() => {
                this.emit("warning", t)
            })
        };
        this._trackers = i.map(e => {
            let t;
            try {
                t = new URL(e)
            } catch (Ga) {
                return o(new Error("Invalid tracker URL: " + e)), null
            }
            const r = t.port;
            if (r < 0 || r > 65535) return o(new Error("Invalid tracker port: " + e)), null;
            const n = t.protocol;
            return "http:" !== n && "https:" !== n || "function" != typeof ae ? "udp:" === n && "function" == typeof ae ? new ae(this, e) : "ws:" !== n && "wss:" !== n || !s || "ws:" === n && "undefined" != typeof window && "https:" === window.location.protocol ? (o(new Error("Unsupported tracker protocol: " + e)), null) : new qi(this, e) : new ae(this, e)
        }).filter(Boolean)
    }
    start(e)
    {
        (e = this._defaultAnnounceOpts(e)).event = "started", r("send `start` %o", e), this._announce(e), this._trackers.forEach(e => {
            e.setInterval()
        })
    }
    stop(e)
    {
        (e = this._defaultAnnounceOpts(e)).event = "stopped", r("send `stop` %o", e), this._announce(e)
    }
    complete(e)
    {
        e || (e = {}), (e = this._defaultAnnounceOpts(e)).event = "completed", r("send `complete` %o", e), this._announce(e)
    }
    update(e)
    {
        (e = this._defaultAnnounceOpts(e)).event && delete e.event, r("send `update` %o", e), this._announce(e)
    }
    _announce(e)
    {
        this._trackers.forEach(t => {
            t.announce(e)
        })
    }
    scrape(e)
    {
        r("send `scrape`"), e || (e = {}), this._trackers.forEach(t => {
            t.scrape(e)
        })
    }
    setInterval(e)
    {
        r("setInterval %d", e), this._trackers.forEach(t => {
            t.setInterval(e)
        })
    }
    destroy(e)
    {
        if (this.destroyed) return;
        this.destroyed = !0, r("destroy");
        const t = this._trackers.map(e => t => {
            e.destroy(t)
        });
        Mt(t, e), this._trackers = [], this._getAnnounceOpts = null
    }
    _defaultAnnounceOpts(e = {})
    {
        return null == e.numwant && (e.numwant = zi.DEFAULT_ANNOUNCE_PEERS), null == e.uploaded && (e.uploaded = 0), null == e.downloaded && (e.downloaded = 0), this._getAnnounceOpts && (e = Object.assign({}, e, this._getAnnounceOpts())), e
    }
}
n.scrape = (e, r) => {
    if (r = Ot(r), !e.infoHash) throw new Error("Option `infoHash` is required");
    if (!e.announce) throw new Error("Option `announce` is required");
    const i = Object.assign({}, e, {
        infoHash: Array.isArray(e.infoHash) ? e.infoHash[0] : e.infoHash,
        peerId: t.from("01234567890123456789"),
        port: 6881
    }), s = new n(i);
    s.once("error", r), s.once("warning", r);
    let o = Array.isArray(e.infoHash) ? e.infoHash.length : 1;
    const a = {};
    return s.on("scrape", e => {
        if (o -= 1, a[e.infoHash] = e, 0 === o) {
            s.destroy();
            const e = Object.keys(a);
            1 === e.length ? r(null, a[e[0]]) : r(null, a)
        }
    }), e.infoHash = Array.isArray(e.infoHash) ? e.infoHash.map(e => t.from(e, "hex")) : t.from(e.infoHash, "hex"), s.scrape({infoHash: e.infoHash}), s
}, Gi = n
}).
call(this)
}).
call(this, _e, y({}).Buffer);
var Xi = {};
(function (e) {
    (function () {
        const t = Xt("torrent-discovery"), r = $.EventEmitter;
        Xi = class extends r {
            constructor(t) {
                if (super(), !t.peerId) throw new Error("Option `peerId` is required");
                if (!t.infoHash) throw new Error("Option `infoHash` is required");
                if (!e.browser && !t.port) throw new Error("Option `port` is required");
                this.peerId = "string" == typeof t.peerId ? t.peerId : t.peerId.toString("hex"), this.infoHash = "string" == typeof t.infoHash ? t.infoHash.toLowerCase() : t.infoHash.toString("hex"), this._port = t.port, this._userAgent = t.userAgent, this.destroyed = !1, this._announce = t.announce || [], this._intervalMs = t.intervalMs || 9e5, this._trackerOpts = null, this._dhtAnnouncing = !1, this._dhtTimeout = !1, this._internalDHT = !1, this._onWarning = e => {
                    this.emit("warning", e)
                }, this._onError = e => {
                    this.emit("error", e)
                }, this._onDHTPeer = (e, t) => {
                    t.toString("hex") === this.infoHash && this.emit("peer", `${e.host}:${e.port}`, "dht")
                }, this._onTrackerPeer = e => {
                    this.emit("peer", e, "tracker")
                }, this._onTrackerAnnounce = () => {
                    this.emit("trackerAnnounce")
                }, this._onLSDPeer = (e, t) => {
                    this.emit("peer", e, "lsd")
                };
                const r = (e, t) => {
                    const r = new ae(t);
                    return r.on("warning", this._onWarning), r.on("error", this._onError), r.listen(e), this._internalDHT = !0, r
                };
                !1 === t.tracker ? this.tracker = null : t.tracker && "object" == typeof t.tracker ? (this._trackerOpts = Object.assign({}, t.tracker), this.tracker = this._createTracker()) : this.tracker = this._createTracker(), !1 === t.dht || "function" != typeof ae ? this.dht = null : t.dht && "function" == typeof t.dht.addNode ? this.dht = t.dht : t.dht && "object" == typeof t.dht ? this.dht = r(t.dhtPort, t.dht) : this.dht = r(t.dhtPort), this.dht && (this.dht.on("peer", this._onDHTPeer), this._dhtAnnounce()), !1 === t.lsd || "function" != typeof ae ? this.lsd = null : this.lsd = this._createLSD()
            }

            updatePort(e) {
                e !== this._port && (this._port = e, this.dht && this._dhtAnnounce(), this.tracker && (this.tracker.stop(), this.tracker.destroy(() => {
                    this.tracker = this._createTracker()
                })))
            }

            complete(e) {
                this.tracker && this.tracker.complete(e)
            }

            destroy(e) {
                if (this.destroyed) return;
                this.destroyed = !0, clearTimeout(this._dhtTimeout);
                const t = [];
                this.tracker && (this.tracker.stop(), this.tracker.removeListener("warning", this._onWarning), this.tracker.removeListener("error", this._onError), this.tracker.removeListener("peer", this._onTrackerPeer), this.tracker.removeListener("update", this._onTrackerAnnounce), t.push(e => {
                    this.tracker.destroy(e)
                })), this.dht && this.dht.removeListener("peer", this._onDHTPeer), this._internalDHT && (this.dht.removeListener("warning", this._onWarning), this.dht.removeListener("error", this._onError), t.push(e => {
                    this.dht.destroy(e)
                })), this.lsd && (this.lsd.removeListener("warning", this._onWarning), this.lsd.removeListener("error", this._onError), this.lsd.removeListener("peer", this._onLSDPeer), t.push(e => {
                    this.lsd.destroy(e)
                })), Mt(t, e), this.dht = null, this.tracker = null, this.lsd = null, this._announce = null
            }

            _createTracker() {
                const e = Object.assign({}, this._trackerOpts, {
                    infoHash: this.infoHash,
                    announce: this._announce,
                    peerId: this.peerId,
                    port: this._port,
                    userAgent: this._userAgent
                }), t = new Gi(e);
                return t.on("warning", this._onWarning), t.on("error", this._onError), t.on("peer", this._onTrackerPeer), t.on("update", this._onTrackerAnnounce), t.setInterval(this._intervalMs), t.start(), t
            }

            _dhtAnnounce() {
                this._dhtAnnouncing || (t("dht announce"), this._dhtAnnouncing = !0, clearTimeout(this._dhtTimeout), this.dht.announce(this.infoHash, this._port, e => {
                    this._dhtAnnouncing = !1, t("dht announce complete"), e && this.emit("warning", e), this.emit("dhtAnnounce"), this.destroyed || (this._dhtTimeout = setTimeout(() => {
                        this._dhtAnnounce()
                    }, this._intervalMs + Math.floor(Math.random() * this._intervalMs / 5)), this._dhtTimeout.unref && this._dhtTimeout.unref())
                }))
            }

            _createLSD() {
                const e = Object.assign({}, {infoHash: this.infoHash, peerId: this.peerId, port: this._port}),
                    t = new ae(e);
                return t.on("warning", this._onWarning), t.on("error", this._onError), t.on("peer", this._onLSDPeer), t.start(), t
            }
        }
    }).call(this)
}).call(this, _e);
var Yi = {};
(function (e) {
    (function () {
        function t(e, r) {
            if (!(this instanceof t)) return new t(e, r);
            if (r || (r = {}), this.chunkLength = Number(e), !this.chunkLength) throw new Error("First argument must be a chunk length");
            this.chunks = [], this.closed = !1, this.length = Number(r.length) || 1 / 0, this.length !== 1 / 0 && (this.lastChunkLength = this.length % this.chunkLength || this.chunkLength, this.lastChunkIndex = Math.ceil(this.length / this.chunkLength) - 1)
        }

        function r(t, r, n) {
            e.nextTick((function () {
                t && t(r, n)
            }))
        }

        Yi = t, t.prototype.put = function (e, t, n) {
            if (this.closed) return r(n, new Error("Storage is closed"));
            var i = e === this.lastChunkIndex;
            return i && t.length !== this.lastChunkLength ? r(n, new Error("Last chunk length must be " + this.lastChunkLength)) : i || t.length === this.chunkLength ? (this.chunks[e] = t, void r(n, null)) : r(n, new Error("Chunk length must be " + this.chunkLength))
        }, t.prototype.get = function (e, t, n) {
            if ("function" == typeof t) return this.get(e, null, t);
            if (this.closed) return r(n, new Error("Storage is closed"));
            var i = this.chunks[e];
            if (!i) {
                var s = new Error("Chunk not found");
                return s.notFound = !0, r(n, s)
            }
            if (!t) return r(n, null, i);
            var o = t.offset || 0, a = t.length || i.length - o;
            r(n, null, i.slice(o, a + o))
        }, t.prototype.close = t.prototype.destroy = function (e) {
            if (this.closed) return r(e, new Error("Storage is closed"));
            this.closed = !0, this.chunks = null, r(e, null)
        }
    }).call(this)
}).call(this, _e);
var Ji = {};
(function (e) {
    (function () {
        Ji = function (t, r, n) {
            if ("number" != typeof r) throw new Error("second argument must be a Number");
            var i, s, o, a, h, u = !0;

            function c(t) {
                function r() {
                    n && n(t, i), n = null
                }

                u ? e.nextTick(r) : r()
            }

            function d(e, r, n) {
                if (i[e] = n, r && (h = !0), 0 == --o || r) c(r); else if (!h && l < s) {
                    var u;
                    a ? (u = a[l], l += 1, t[u]((function (e, t) {
                        d(u, e, t)
                    }))) : (u = l, l += 1, t[u]((function (e, t) {
                        d(u, e, t)
                    })))
                }
            }

            Array.isArray(t) ? (i = [], o = s = t.length) : (a = Object.keys(t), i = {}, o = s = a.length);
            var l = r;
            o ? a ? a.some((function (e, n) {
                if (t[e]((function (t, r) {
                    d(e, t, r)
                })), n === r - 1) return !0
            })) : t.some((function (e, t) {
                if (e((function (e, r) {
                    d(t, e, r)
                })), t === r - 1) return !0
            })) : c(null), u = !1
        }
    }).call(this)
}).call(this, _e);
var Qi = {};
(function (e) {
    (function () {
        class t {
            constructor(e) {
                this.length = e, this.missing = e, this.sources = null, this._chunks = Math.ceil(e / 16384), this._remainder = e % 16384 || 16384, this._buffered = 0, this._buffer = null, this._cancellations = null, this._reservations = 0, this._flushed = !1
            }

            chunkLength(e) {
                return e === this._chunks - 1 ? this._remainder : 16384
            }

            chunkLengthRemaining(e) {
                return this.length - 16384 * e
            }

            chunkOffset(e) {
                return 16384 * e
            }

            reserve() {
                return this.init() ? this._cancellations.length ? this._cancellations.pop() : this._reservations < this._chunks ? this._reservations++ : -1 : -1
            }

            reserveRemaining() {
                if (!this.init()) return -1;
                if (this._reservations < this._chunks) {
                    const e = this._reservations;
                    return this._reservations = this._chunks, e
                }
                return -1
            }

            cancel(e) {
                this.init() && this._cancellations.push(e)
            }

            cancelRemaining(e) {
                this.init() && (this._reservations = e)
            }

            get(e) {
                return this.init() ? this._buffer[e] : null
            }

            set(e, t, r) {
                if (!this.init()) return !1;
                const n = t.length, i = Math.ceil(n / 16384);
                for (let s = 0; s < i; s++) if (!this._buffer[e + s]) {
                    const n = 16384 * s, i = t.slice(n, n + 16384);
                    this._buffered++, this._buffer[e + s] = i, this.missing -= i.length, this.sources.includes(r) || this.sources.push(r)
                }
                return this._buffered === this._chunks
            }

            flush() {
                if (!this._buffer || this._chunks !== this._buffered) return null;
                const t = e.concat(this._buffer, this.length);
                return this._buffer = null, this._cancellations = null, this.sources = null, this._flushed = !0, t
            }

            init() {
                return !(this._flushed || !this._buffer && (this._buffer = new Array(this._chunks), this._cancellations = [], this.sources = [], 0))
            }
        }

        Object.defineProperty(t, "BLOCK_LENGTH", {value: 16384}), Qi = t
    }).call(this)
}).call(this, y({}).Buffer);
var es = function () {
}, ts = function (e, t, r) {
    if ("function" == typeof t) return ts(e, null, t);
    t || (t = {}), r = Ot(r || es);
    var n = e._writableState, i = e._readableState, s = t.readable || !1 !== t.readable && e.readable,
        o = t.writable || !1 !== t.writable && e.writable, a = function () {
            e.writable || h()
        }, h = function () {
            o = !1, s || r.call(e)
        }, u = function () {
            s = !1, o || r.call(e)
        }, c = function (t) {
            r.call(e, t ? new Error("exited with error code: " + t) : null)
        }, d = function (t) {
            r.call(e, t)
        }, l = function () {
            return (!s || i && i.ended) && (!o || n && n.ended) ? void 0 : r.call(e, new Error("premature close"))
        }, f = function () {
            e.req.on("finish", h)
        };
    return function (e) {
        return e.setHeader && "function" == typeof e.abort
    }(e) ? (e.on("complete", h), e.on("abort", l), e.req ? f() : e.on("request", f)) : o && !n && (e.on("end", a), e.on("close", a)), function (e) {
        return e.stdio && Array.isArray(e.stdio) && 3 === e.stdio.length
    }(e) && e.on("exit", c), e.on("end", u), e.on("finish", h), !1 !== t.error && e.on("error", d), e.on("close", l), function () {
        e.removeListener("complete", h), e.removeListener("abort", l), e.removeListener("request", f), e.req && e.req.removeListener("finish", h), e.removeListener("end", a), e.removeListener("close", a), e.removeListener("finish", h), e.removeListener("exit", c), e.removeListener("end", u), e.removeListener("error", d), e.removeListener("close", l)
    }
}, rs = ts, ns = {};
(function (e) {
    (function () {
        var t = function () {
        }, r = /^v?\.0/.test(e.version), n = function (e) {
            return "function" == typeof e
        }, i = function (e, i, s, o) {
            o = Ot(o);
            var a = !1;
            e.on("close", (function () {
                a = !0
            })), rs(e, {readable: i, writable: s}, (function (e) {
                if (e) return o(e);
                a = !0, o()
            }));
            var h = !1;
            return function (i) {
                if (!a && !h) return h = !0, function (e) {
                    return !!r && !!ae && (e instanceof (ae.ReadStream || t) || e instanceof (ae.WriteStream || t)) && n(e.close)
                }(e) ? e.close(t) : function (e) {
                    return e.setHeader && n(e.abort)
                }(e) ? e.abort() : n(e.destroy) ? e.destroy() : void o(i || new Error("stream was destroyed"))
            }
        }, s = function (e) {
            e()
        }, o = function (e, t) {
            return e.pipe(t)
        };
        ns = function () {
            var e, r = Array.prototype.slice.call(arguments), a = n(r[r.length - 1] || t) && r.pop() || t;
            if (Array.isArray(r[0]) && (r = r[0]), r.length < 2) throw new Error("pump requires two streams per minimum");
            var h = r.map((function (t, n) {
                var o = n < r.length - 1;
                return i(t, o, n > 0, (function (t) {
                    e || (e = t), t && h.forEach(s), o || (h.forEach(s), a(e))
                }))
            }));
            return r.reduce(o)
        }
    }).call(this)
}).call(this, _e);
var is = {};
(function (e) {
    (function () {
        const {EventEmitter: t} = $, r = Di.default, n = Xt("ut_metadata");
        is = i => {
            class s extends t {
                constructor(t) {
                    super(), this._wire = t, this._fetching = !1, this._metadataComplete = !1, this._metadataSize = null, this._remainingRejects = null, this._bitfield = new r(0, {grow: 1e3}), e.isBuffer(i) && this.setMetadata(i)
                }

                onHandshake(e, t, r) {
                    this._infoHash = e
                }

                onExtendedHandshake(e) {
                    return e.m && e.m.ut_metadata ? e.metadata_size ? "number" != typeof e.metadata_size || 1e7 < e.metadata_size || e.metadata_size <= 0 ? this.emit("warning", new Error("Peer gave invalid metadata size")) : (this._metadataSize = e.metadata_size, this._numPieces = Math.ceil(this._metadataSize / 16384), this._remainingRejects = 2 * this._numPieces, void this._requestPieces()) : this.emit("warning", new Error("Peer does not have metadata")) : this.emit("warning", new Error("Peer does not support ut_metadata"))
                }

                onMessage(e) {
                    let t, r;
                    try {
                        const n = e.toString(), i = n.indexOf("ee") + 2;
                        t = q.decode(n.substring(0, i)), r = e.slice(i)
                    } catch (Ga) {
                        return
                    }
                    switch (t.msg_type) {
                        case 0:
                            this._onRequest(t.piece);
                            break;
                        case 1:
                            this._onData(t.piece, r, t.total_size);
                            break;
                        case 2:
                            this._onReject(t.piece)
                    }
                }

                fetch() {
                    this._metadataComplete || (this._fetching = !0, this._metadataSize && this._requestPieces())
                }

                cancel() {
                    this._fetching = !1
                }

                setMetadata(e) {
                    if (this._metadataComplete) return !0;
                    n("set metadata");
                    try {
                        const t = q.decode(e).info;
                        t && (e = q.encode(t))
                    } catch (Ga) {
                    }
                    return !(this._infoHash && this._infoHash !== Wt.sync(e) || (this.cancel(), this.metadata = e, this._metadataComplete = !0, this._metadataSize = this.metadata.length, this._wire.extendedHandshake.metadata_size = this._metadataSize, this.emit("metadata", q.encode({info: q.decode(this.metadata)})), 0))
                }

                _send(t, r) {
                    let n = q.encode(t);
                    e.isBuffer(r) && (n = e.concat([n, r])), this._wire.extended("ut_metadata", n)
                }

                _request(e) {
                    this._send({msg_type: 0, piece: e})
                }

                _data(e, t, r) {
                    const n = {msg_type: 1, piece: e};
                    "number" == typeof r && (n.total_size = r), this._send(n, t)
                }

                _reject(e) {
                    this._send({msg_type: 2, piece: e})
                }

                _onRequest(e) {
                    if (!this._metadataComplete) return void this._reject(e);
                    const t = 16384 * e;
                    let r = t + 16384;
                    r > this._metadataSize && (r = this._metadataSize);
                    const n = this.metadata.slice(t, r);
                    this._data(e, n, this._metadataSize)
                }

                _onData(e, t, r) {
                    t.length > 16384 || !this._fetching || (t.copy(this.metadata, 16384 * e), this._bitfield.set(e), this._checkDone())
                }

                _onReject(e) {
                    this._remainingRejects > 0 && this._fetching ? (this._request(e), this._remainingRejects -= 1) : this.emit("warning", new Error('Peer sent "reject" too much'))
                }

                _requestPieces() {
                    if (this._fetching) {
                        this.metadata = e.alloc(this._metadataSize);
                        for (let e = 0; e < this._numPieces; e++) this._request(e)
                    }
                }

                _checkDone() {
                    let e = !0;
                    for (let t = 0; t < this._numPieces; t++) if (!this._bitfield.get(t)) {
                        e = !1;
                        break
                    }
                    e && (this.setMetadata(this.metadata) || this._failedMetadata())
                }

                _failedMetadata() {
                    this._bitfield = new r(0, {grow: 1e3}), this._remainingRejects -= this._numPieces, this._remainingRejects > 0 ? this._requestPieces() : this.emit("warning", new Error("Peer sent invalid metadata"))
                }
            }

            return s.prototype.name = "ut_metadata", s
        }
    }).call(this)
}).call(this, y({}).Buffer);
var ss = y({}).Buffer, os = hs, as = "undefined" != typeof window && window.MediaSource;

function hs(e, t) {
    var r = this;
    if (!(r instanceof hs)) return new hs(e, t);
    if (!as) throw new Error("web browser lacks MediaSource support");
    t || (t = {}), r._debug = t.debug, r._bufferDuration = t.bufferDuration || 60, r._elem = e, r._mediaSource = new as, r._streams = [], r.detailedError = null, r._errorHandler = function () {
        r._elem.removeEventListener("error", r._errorHandler), r._streams.slice().forEach((function (e) {
            e.destroy(r._elem.error)
        }))
    }, r._elem.addEventListener("error", r._errorHandler), r._elem.src = window.URL.createObjectURL(r._mediaSource)
}

function us(e, t) {
    var r = this;
    if (dt.Writable.call(r), r._wrapper = e, r._elem = e._elem, r._mediaSource = e._mediaSource, r._allStreams = e._streams, r._allStreams.push(r), r._bufferDuration = e._bufferDuration, r._sourceBuffer = null, r._debugBuffers = [], r._openHandler = function () {
        r._onSourceOpen()
    }, r._flowHandler = function () {
        r._flow()
    }, r._errorHandler = function (e) {
        r.destroyed || r.emit("error", e)
    }, "string" == typeof t) r._type = t, "open" === r._mediaSource.readyState ? r._createSourceBuffer() : r._mediaSource.addEventListener("sourceopen", r._openHandler); else if (null === t._sourceBuffer) t.destroy(), r._type = t._type, r._mediaSource.addEventListener("sourceopen", r._openHandler); else {
        if (!t._sourceBuffer) throw new Error("The argument to MediaElementWrapper.createWriteStream must be a string or a previous stream returned from that function");
        t.destroy(), r._type = t._type, r._sourceBuffer = t._sourceBuffer, r._debugBuffers = t._debugBuffers, r._sourceBuffer.addEventListener("updateend", r._flowHandler), r._sourceBuffer.addEventListener("error", r._errorHandler)
    }
    r._elem.addEventListener("timeupdate", r._flowHandler), r.on("error", (function (e) {
        r._wrapper.error(e)
    })), r.on("finish", (function () {
        if (!r.destroyed && (r._finished = !0, r._allStreams.every((function (e) {
            return e._finished
        })))) {
            r._wrapper._dumpDebugData();
            try {
                r._mediaSource.endOfStream()
            } catch (Ga) {
            }
        }
    }))
}

hs.prototype.createWriteStream = function (e) {
    return new us(this, e)
}, hs.prototype.error = function (e) {
    this.detailedError || (this.detailedError = e), this._dumpDebugData();
    try {
        this._mediaSource.endOfStream("decode")
    } catch (e) {
    }
    try {
        window.URL.revokeObjectURL(this._elem.src)
    } catch (e) {
    }
}, hs.prototype._dumpDebugData = function () {
    this._debug && (this._debug = !1, this._streams.forEach((function (e, t) {
        var r, n, i;
        r = e._debugBuffers, n = "mediasource-stream-" + t, (i = document.createElement("a")).href = window.URL.createObjectURL(new window.Blob(r)), i.download = n, i.click()
    })))
}, De(us, dt.Writable), us.prototype._onSourceOpen = function () {
    this.destroyed || (this._mediaSource.removeEventListener("sourceopen", this._openHandler), this._createSourceBuffer())
}, us.prototype.destroy = function (e) {
    this.destroyed || (this.destroyed = !0, this._allStreams.splice(this._allStreams.indexOf(this), 1), this._mediaSource.removeEventListener("sourceopen", this._openHandler), this._elem.removeEventListener("timeupdate", this._flowHandler), this._sourceBuffer && (this._sourceBuffer.removeEventListener("updateend", this._flowHandler), this._sourceBuffer.removeEventListener("error", this._errorHandler), "open" === this._mediaSource.readyState && this._sourceBuffer.abort()), e && this.emit("error", e), this.emit("close"))
}, us.prototype._createSourceBuffer = function () {
    if (!this.destroyed) if (as.isTypeSupported(this._type)) {
        if (this._sourceBuffer = this._mediaSource.addSourceBuffer(this._type), this._sourceBuffer.addEventListener("updateend", this._flowHandler), this._sourceBuffer.addEventListener("error", this._errorHandler), this._cb) {
            var e = this._cb;
            this._cb = null, e()
        }
    } else this.destroy(new Error("The provided type is not supported"))
}, us.prototype._write = function (e, t, r) {
    var n = this;
    if (!n.destroyed) if (n._sourceBuffer) {
        if (n._sourceBuffer.updating) return r(new Error("Cannot append buffer while source buffer updating"));
        var i = function (e) {
            if (e instanceof Uint8Array) {
                if (0 === e.byteOffset && e.byteLength === e.buffer.byteLength) return e.buffer;
                if ("function" == typeof e.buffer.slice) return e.buffer.slice(e.byteOffset, e.byteOffset + e.byteLength)
            }
            if (ss.isBuffer(e)) {
                for (var t = new Uint8Array(e.length), r = e.length, n = 0; n < r; n++) t[n] = e[n];
                return t.buffer
            }
            throw new Error("Argument must be a Buffer")
        }(e);
        n._wrapper._debug && n._debugBuffers.push(i);
        try {
            n._sourceBuffer.appendBuffer(i)
        } catch (Ga) {
            return void n.destroy(Ga)
        }
        n._cb = r
    } else n._cb = function (i) {
        if (i) return r(i);
        n._write(e, t, r)
    }
}, us.prototype._flow = function () {
    if (!this.destroyed && this._sourceBuffer && !this._sourceBuffer.updating && !("open" === this._mediaSource.readyState && this._getBufferDuration() > this._bufferDuration) && this._cb) {
        var e = this._cb;
        this._cb = null, e()
    }
}, us.prototype._getBufferDuration = function () {
    for (var e = this._sourceBuffer.buffered, t = this._elem.currentTime, r = -1, n = 0; n < e.length; n++) {
        var i = e.start(n), s = e.end(n) + 0;
        if (i > t) break;
        (r >= 0 || t <= s) && (r = s)
    }
    var o = r - t;
    return o < 0 && (o = 0), o
};
var cs = function (e, t) {
    if (null != t && "string" != typeof t) throw new Error("Invalid mimetype, expected string.");
    return new Promise((r, n) => {
        const i = [];
        e.on("data", e => i.push(e)).once("end", () => {
            const e = null != t ? new Blob(i, {type: t}) : new Blob(i);
            r(e)
        }).once("error", n)
    })
}, ds = async function (e, t) {
    const r = await cs(e, t);
    return URL.createObjectURL(r)
}, ls = {};
(function (e) {
    (function () {
        var t = function () {
            try {
                if (!e.isEncoding("latin1")) return !1;
                var t = e.alloc ? e.alloc(4) : new e(4);
                return t.fill("ab", "ucs2"), "61006200" === t.toString("hex")
            } catch (r) {
                return !1
            }
        }();

        function r(e, t, r, n) {
            if (r < 0 || n > e.length) throw new RangeError("Out of range index");
            return r >>>= 0, (n = void 0 === n ? e.length : n >>> 0) > r && e.fill(t, r, n), e
        }

        ls = function (n, i, s, o, a) {
            if (t) return n.fill(i, s, o, a);
            if ("number" == typeof i) return r(n, i, s, o);
            if ("string" == typeof i) {
                if ("string" == typeof s ? (a = s, s = 0, o = n.length) : "string" == typeof o && (a = o, o = n.length), void 0 !== a && "string" != typeof a) throw new TypeError("encoding must be a string");
                if ("latin1" === a && (a = "binary"), "string" == typeof a && !e.isEncoding(a)) throw new TypeError("Unknown encoding: " + a);
                if ("" === i) return r(n, 0, s, o);
                if (function (e) {
                    return 1 === e.length && e.charCodeAt(0) < 256
                }(i)) return r(n, i.charCodeAt(0), s, o);
                i = new e(i, a)
            }
            return e.isBuffer(i) ? function (e, t, r, n) {
                if (r < 0 || n > e.length) throw new RangeError("Out of range index");
                if (n <= r) return e;
                r >>>= 0, n = void 0 === n ? e.length : n >>> 0;
                for (var i = r, s = t.length; i <= n - s;) t.copy(e, i), i += s;
                return i !== n && t.copy(e, i, 0, n - i), e
            }(n, i, s, o) : r(n, 0, s, o)
        }
    }).call(this)
}).call(this, y({}).Buffer);
var fs = {};
(function (e) {
    (function () {
        fs = function (t) {
            if ("number" != typeof t) throw new TypeError('"size" argument must be a number');
            if (t < 0) throw new RangeError('"size" argument must not be negative');
            return e.allocUnsafe ? e.allocUnsafe(t) : new e(t)
        }
    }).call(this)
}).call(this, y({}).Buffer);
var ps = {};
(function (e) {
    (function () {
        ps = function (t, r, n) {
            if ("number" != typeof t) throw new TypeError('"size" argument must be a number');
            if (t < 0) throw new RangeError('"size" argument must not be negative');
            if (e.alloc) return e.alloc(t, r, n);
            var i = fs(t);
            return 0 === t ? i : void 0 === r ? ls(i, 0) : ("string" != typeof n && (n = void 0), ls(i, r, n))
        }
    }).call(this)
}).call(this, y({}).Buffer);
var ms = {}, gs = Math.pow(2, 32);
ms.encode = function (e, t, r) {
    t || (t = ps(8)), r || (r = 0);
    var n = Math.floor(e / gs), i = e - n * gs;
    return t.writeUInt32BE(n, r), t.writeUInt32BE(i, r + 4), t
}, ms.decode = function (e, t) {
    t || (t = 0);
    var r = e.readUInt32BE(t), n = e.readUInt32BE(t + 4);
    return r * gs + n
}, ms.encode.bytes = 8, ms.decode.bytes = 8;
var _s = {};
(function (e) {
    (function () {
        var t = {3: "ESDescriptor", 4: "DecoderConfigDescriptor", 5: "DecoderSpecificInfo", 6: "SLConfigDescriptor"};
        _s.Descriptor = {}, _s.Descriptor.decode = function (r, n, i) {
            var s, o, a = r.readUInt8(n), h = n + 1, u = 0;
            do {
                u = u << 7 | 127 & (s = r.readUInt8(h++))
            } while (128 & s);
            var c = t[a];
            return (o = _s[c] ? _s[c].decode(r, h, i) : {buffer: e.from(r.slice(h, h + u))}).tag = a, o.tagName = c, o.length = h - n + u, o.contentsLen = u, o
        }, _s.DescriptorArray = {}, _s.DescriptorArray.decode = function (e, r, n) {
            for (var i = r, s = {}; i + 2 <= n;) {
                var o = _s.Descriptor.decode(e, i, n);
                i += o.length, s[t[o.tag] || "Descriptor" + o.tag] = o
            }
            return s
        }, _s.ESDescriptor = {}, _s.ESDescriptor.decode = function (e, t, r) {
            var n = e.readUInt8(t + 2), i = t + 3;
            return 128 & n && (i += 2), 64 & n && (i += e.readUInt8(i) + 1), 32 & n && (i += 2), _s.DescriptorArray.decode(e, i, r)
        }, _s.DecoderConfigDescriptor = {}, _s.DecoderConfigDescriptor.decode = function (e, t, r) {
            var n = e.readUInt8(t), i = _s.DescriptorArray.decode(e, t + 13, r);
            return i.oti = n, i
        }
    }).call(this)
}).call(this, y({}).Buffer);
var ys = {};
(function (e) {
    (function () {
        var t = h({}), r = e.alloc(0);

        class n extends dt.PassThrough {
            constructor(e) {
                super(), this._parent = e, this.destroyed = !1
            }

            destroy(e) {
                this.destroyed || (this.destroyed = !0, this._parent.destroy(e), e && this.emit("error", e), this.emit("close"))
            }
        }

        ys = class extends dt.Writable {
            constructor(e) {
                super(e), this.destroyed = !1, this._pending = 0, this._missing = 0, this._ignoreEmpty = !1, this._buf = null, this._str = null, this._cb = null, this._ondrain = null, this._writeBuffer = null, this._writeCb = null, this._ondrain = null, this._kick()
            }

            destroy(e) {
                this.destroyed || (this.destroyed = !0, e && this.emit("error", e), this.emit("close"))
            }

            _write(e, t, n) {
                if (!this.destroyed) {
                    for (var i = !this._str || !this._str._writableState.needDrain; e.length && !this.destroyed;) {
                        if (!this._missing && !this._ignoreEmpty) return this._writeBuffer = e, void (this._writeCb = n);
                        var s = e.length < this._missing ? e.length : this._missing;
                        if (this._buf ? e.copy(this._buf, this._buf.length - this._missing) : this._str && (i = this._str.write(s === e.length ? e : e.slice(0, s))), this._missing -= s, !this._missing) {
                            var o = this._buf, a = this._cb, h = this._str;
                            this._buf = this._cb = this._str = this._ondrain = null, i = !0, this._ignoreEmpty = !1, h && h.end(), a && a(o)
                        }
                        e = s === e.length ? r : e.slice(s)
                    }
                    if (this._pending && !this._missing) return this._writeBuffer = e, void (this._writeCb = n);
                    i ? n() : this._ondrain(n)
                }
            }

            _buffer(t, r) {
                this._missing = t, this._buf = e.alloc(t), this._cb = r
            }

            _stream(e, t) {
                return this._missing = e, this._str = new n(this), this._ondrain = (r = this._str, i = "drain", s = null, r.on(i, (function (e) {
                    if (s) {
                        var t = s;
                        s = null, t(e)
                    }
                })), function (e) {
                    s = e
                }), this._pending++, this._str.on("end", () => {
                    this._pending--, this._kick()
                }), this._cb = t, this._str;
                var r, i, s
            }

            _readBox() {
                const r = (n, i) => {
                    this._buffer(n, n => {
                        i = i ? e.concat([i, n]) : n;
                        var s = t.readHeaders(i);
                        "number" == typeof s ? r(s - i.length, i) : (this._pending++, this._headers = s, this.emit("box", s))
                    })
                };
                r(8)
            }

            stream() {
                if (!this._headers) throw new Error("this function can only be called once after 'box' is emitted");
                var e = this._headers;
                return this._headers = null, this._stream(e.contentLen, null)
            }

            decode(e) {
                if (!this._headers) throw new Error("this function can only be called once after 'box' is emitted");
                var r = this._headers;
                this._headers = null, this._buffer(r.contentLen, n => {
                    var i = t.decodeWithoutHeaders(r, n);
                    e(i), this._pending--, this._kick()
                })
            }

            ignore() {
                if (!this._headers) throw new Error("this function can only be called once after 'box' is emitted");
                var e = this._headers;
                this._headers = null, this._missing = e.contentLen, 0 === this._missing && (this._ignoreEmpty = !0), this._cb = () => {
                    this._pending--, this._kick()
                }
            }

            _kick() {
                if (!this._pending && (this._buf || this._str || this._readBox(), this._writeBuffer)) {
                    var e = this._writeCb, t = this._writeBuffer;
                    this._writeBuffer = null, this._writeCb = null, this._write(t, null, e)
                }
            }
        }
    }).call(this)
}).call(this, y({}).Buffer);
var bs = {};
(function (e, t) {
    (function () {
        var r = h({});

        function n() {
        }

        class i extends dt.PassThrough {
            constructor(e) {
                super(), this._parent = e, this.destroyed = !1
            }

            destroy(e) {
                this.destroyed || (this.destroyed = !0, this._parent.destroy(e), e && this.emit("error", e), this.emit("close"))
            }
        }

        bs = class extends dt.Readable {
            constructor(e) {
                super(e), this.destroyed = !1, this._finalized = !1, this._reading = !1, this._stream = null, this._drain = null, this._want = !1, this._onreadable = () => {
                    this._want && (this._want = !1, this._read())
                }, this._onend = () => {
                    this._stream = null
                }
            }

            mdat(e, t) {
                this.mediaData(e, t)
            }

            mediaData(e, t) {
                var r = new i(this);
                return this.box({type: "mdat", contentLength: e, encodeBufferLen: 8, stream: r}, t), r
            }

            box(i, s) {
                if (s || (s = n), this.destroyed) return s(new Error("Encoder is destroyed"));
                var o;
                if (i.encodeBufferLen && (o = t.alloc(i.encodeBufferLen)), i.stream) i.buffer = null, o = r.encode(i, o), this.push(o), this._stream = i.stream, this._stream.on("readable", this._onreadable), this._stream.on("end", this._onend), this._stream.on("end", s), this._forward(); else {
                    if (o = r.encode(i, o), this.push(o)) return e.nextTick(s);
                    this._drain = s
                }
            }

            destroy(e) {
                if (!this.destroyed) {
                    if (this.destroyed = !0, this._stream && this._stream.destroy && this._stream.destroy(), this._stream = null, this._drain) {
                        var t = this._drain;
                        this._drain = null, t(e)
                    }
                    e && this.emit("error", e), this.emit("close")
                }
            }

            finalize() {
                this._finalized = !0, this._stream || this._drain || this.push(null)
            }

            _forward() {
                if (this._stream) for (; !this.destroyed;) {
                    var e = this._stream.read();
                    if (!e) return void (this._want = !!this._stream);
                    if (!this.push(e)) return
                }
            }

            _read() {
                if (!this._reading && !this.destroyed) {
                    if (this._reading = !0, this._stream && this._forward(), this._drain) {
                        var e = this._drain;
                        this._drain = null, e()
                    }
                    this._reading = !1, this._finalized && this.push(null)
                }
            }
        }
    }).call(this)
}).call(this, _e, y({}).Buffer);
var vs = {decode: e => new ys(e), encode: e => new bs(e)};
const {Writable: ws, PassThrough: Es} = dt;
var ks = class extends ws {
    constructor(e, t = {}) {
        super(t), this.destroyed = !1, this._queue = [], this._position = e || 0, this._cb = null, this._buffer = null, this._out = null
    }

    _write(e, t, r) {
        let n = !0;
        for (; ;) {
            if (this.destroyed) return;
            if (0 === this._queue.length) return this._buffer = e, void (this._cb = r);
            this._buffer = null;
            var i = this._queue[0];
            const t = Math.max(i.start - this._position, 0), s = i.end - this._position;
            if (t >= e.length) return this._position += e.length, r(null);
            let o;
            if (s > e.length) {
                this._position += e.length, o = 0 === t ? e : e.slice(t), n = i.stream.write(o) && n;
                break
            }
            this._position += s, o = 0 === t && s === e.length ? e : e.slice(t, s), n = i.stream.write(o) && n, i.last && i.stream.end(), e = e.slice(s), this._queue.shift()
        }
        n ? r(null) : i.stream.once("drain", r.bind(null, null))
    }

    slice(e) {
        if (this.destroyed) return null;
        Array.isArray(e) || (e = [e]);
        const t = new Es;
        return e.forEach((r, n) => {
            this._queue.push({start: r.start, end: r.end, stream: t, last: n === e.length - 1})
        }), this._buffer && this._write(this._buffer, null, this._cb), t
    }

    destroy(e) {
        this.destroyed || (this.destroyed = !0, e && this.emit("error", e))
    }
}, Ss = {};
(function (e) {
    (function () {
        const t = h({});

        class r {
            constructor(e, t) {
                this._entries = e, this._countName = t || "count", this._index = 0, this._offset = 0, this.value = this._entries[0]
            }

            inc() {
                this._offset++, this._offset >= this._entries[this._index][this._countName] && (this._index++, this._offset = 0), this.value = this._entries[this._index]
            }
        }

        Ss = class extends ${constructor(e){super(),this._tracks=[],this._file=e,this._decoder=null,this._findMoov(0)}_findMoov(e) {
            this
        .
            _decoder
        &&
            this
        .
            _decoder
        .

            destroy();

            let
            t = 0;
            this
        .
            _decoder = vs.decode();
            const
            r = this._file.createReadStream({start: e});
            r
        .

            pipe(

            this
        .
            _decoder
        )
            ;
            const
            n = i => {
                "moov" === i.type ? (this._decoder.removeListener("box", n), this._decoder.decode(e => {
                    r.destroy();
                    try {
                        this._processMoov(e)
                    } catch (Ga) {
                        Ga.message = "Cannot parse mp4 file: " + Ga.message, this.emit("error", Ga)
                    }
                })) : i.length < 4096 ? (t += i.length, this._decoder.ignore()) : (this._decoder.removeListener("box", n), t += i.length, r.destroy(), this._decoder.destroy(), this._findMoov(e + t))
            };
            this
        .
            _decoder
        .

            on(

            "box"
        ,
            n
        )
        }
        _processMoov(n)
        {
            const i = n.traks;
            this._tracks = [], this._hasVideo = !1, this._hasAudio = !1;
            for (let e = 0; e < i.length; e++) {
                const t = i[e], o = t.mdia.minf.stbl, a = o.stsd.entries[0], h = t.mdia.hdlr.handlerType;
                let u, c;
                if ("vide" === h && "avc1" === a.type) {
                    if (this._hasVideo) continue;
                    this._hasVideo = !0, u = "avc1", a.avcC && (u += "." + a.avcC.mimeCodec), c = `video/mp4; codecs="${u}"`
                } else {
                    if ("soun" !== h || "mp4a" !== a.type) continue;
                    if (this._hasAudio) continue;
                    this._hasAudio = !0, u = "mp4a", a.esds && a.esds.mimeCodec && (u += "." + a.esds.mimeCodec), c = `audio/mp4; codecs="${u}"`
                }
                const d = [];
                let l = 0, f = 0, p = 0, m = 0, g = 0, _ = 0;
                const y = new r(o.stts.entries);
                let b = null;
                o.ctts && (b = new r(o.ctts.entries));
                let v = 0;
                for (; ;) {
                    var s = o.stsc.entries[g];
                    const e = o.stsz.entries[l], t = y.value.duration, r = b ? b.value.compositionOffset : 0;
                    let n = !0;
                    o.stss && (n = o.stss.entries[v] === l + 1);
                    const i = o.stco || o.co64;
                    if (d.push({
                        size: e,
                        duration: t,
                        dts: _,
                        presentationOffset: r,
                        sync: n,
                        offset: m + i.entries[p]
                    }), ++l >= o.stsz.entries.length) break;
                    if (m += e, ++f >= s.samplesPerChunk) {
                        f = 0, m = 0, p++;
                        const e = o.stsc.entries[g + 1];
                        e && p + 1 >= e.firstChunk && g++
                    }
                    _ += t, y.inc(), b && b.inc(), n && v++
                }
                t.mdia.mdhd.duration = 0, t.tkhd.duration = 0;
                const w = s.sampleDescriptionId, E = {
                    type: "moov",
                    mvhd: n.mvhd,
                    traks: [{
                        tkhd: t.tkhd,
                        mdia: {
                            mdhd: t.mdia.mdhd,
                            hdlr: t.mdia.hdlr,
                            elng: t.mdia.elng,
                            minf: {
                                vmhd: t.mdia.minf.vmhd,
                                smhd: t.mdia.minf.smhd,
                                dinf: t.mdia.minf.dinf,
                                stbl: {
                                    stsd: o.stsd,
                                    stts: {version: 0, flags: 0, entries: []},
                                    ctts: {version: 0, flags: 0, entries: []},
                                    stsc: {version: 0, flags: 0, entries: []},
                                    stsz: {version: 0, flags: 0, entries: []},
                                    stco: {version: 0, flags: 0, entries: []},
                                    stss: {version: 0, flags: 0, entries: []}
                                }
                            }
                        }
                    }],
                    mvex: {
                        mehd: {fragmentDuration: n.mvhd.duration},
                        trexs: [{
                            trackId: t.tkhd.trackId,
                            defaultSampleDescriptionIndex: w,
                            defaultSampleDuration: 0,
                            defaultSampleSize: 0,
                            defaultSampleFlags: 0
                        }]
                    }
                };
                this._tracks.push({
                    fragmentSequence: 1,
                    trackId: t.tkhd.trackId,
                    timeScale: t.mdia.mdhd.timeScale,
                    samples: d,
                    currSample: null,
                    currTime: null,
                    moov: E,
                    mime: c
                })
            }
            if (0 === this._tracks.length) return void this.emit("error", new Error("no playable tracks"));
            n.mvhd.duration = 0, this._ftyp = {
                type: "ftyp",
                brand: "iso5",
                brandVersion: 0,
                compatibleBrands: ["iso5"]
            };
            const o = t.encode(this._ftyp), a = this._tracks.map(r => {
                const n = t.encode(r.moov);
                return {mime: r.mime, init: e.concat([o, n])}
            });
            this.emit("ready", a)
        }
        seek(e)
        {
            if (!this._tracks) throw new Error("Not ready yet; wait for 'ready' event");
            this._fileStream && (this._fileStream.destroy(), this._fileStream = null);
            let t = -1;
            if (this._tracks.map((r, n) => {
                r.outStream && r.outStream.destroy(), r.inStream && (r.inStream.destroy(), r.inStream = null);
                const i = r.outStream = vs.encode(), s = this._generateFragment(n, e);
                if (!s) return i.finalize();
                (-1 === t || s.ranges[0].start < t) && (t = s.ranges[0].start);
                const o = e => {
                    i.destroyed || i.box(e.moof, t => {
                        if (t) return this.emit("error", t);
                        i.destroyed || r.inStream.slice(e.ranges).pipe(i.mediaData(e.length, e => {
                            if (e) return this.emit("error", e);
                            if (i.destroyed) return;
                            const t = this._generateFragment(n);
                            if (!t) return i.finalize();
                            o(t)
                        }))
                    })
                };
                o(s)
            }), t >= 0) {
                const e = this._fileStream = this._file.createReadStream({start: t});
                this._tracks.forEach(r => {
                    r.inStream = new ks(t, {highWaterMark: 1e7}), e.pipe(r.inStream)
                })
            }
            return this._tracks.map(e => e.outStream)
        }
        _findSampleBefore(e, t)
        {
            const r = this._tracks[e], n = Math.floor(r.timeScale * t);
            let i = function (e, t, r, n, i) {
                var s, o;
                if (void 0 === n) n = 0; else if ((n |= 0) < 0 || n >= e.length) throw new RangeError("invalid lower bound");
                if (void 0 === i) i = e.length - 1; else if ((i |= 0) < n || i >= e.length) throw new RangeError("invalid upper bound");
                for (; n <= i;) if ((o = +r(e[s = n + (i - n >>> 1)], t, s, e)) < 0) n = s + 1; else {
                    if (!(o > 0)) return s;
                    i = s - 1
                }
                return ~n
            }(r.samples, n, (e, t) => e.dts + e.presentationOffset - t);
            for (-1 === i ? i = 0 : i < 0 && (i = -i - 2); !r.samples[i].sync;) i--;
            return i
        }
        _generateFragment(e, t)
        {
            const r = this._tracks[e];
            let n;
            if ((n = void 0 !== t ? this._findSampleBefore(e, t) : r.currSample) >= r.samples.length) return null;
            const i = r.samples[n].dts;
            let s = 0;
            const o = [];
            for (var a = n; a < r.samples.length; a++) {
                const e = r.samples[a];
                if (e.sync && e.dts - i >= 1 * r.timeScale) break;
                s += e.size;
                const t = o.length - 1;
                t < 0 || o[t].end !== e.offset ? o.push({start: e.offset, end: e.offset + e.size}) : o[t].end += e.size
            }
            return r.currSample = a, {moof: this._generateMoof(e, n, a), ranges: o, length: s}
        }
        _generateMoof(e, r, n)
        {
            const i = this._tracks[e], s = [];
            let o = 0;
            for (let t = r; t < n; t++) {
                const e = i.samples[t];
                e.presentationOffset < 0 && (o = 1), s.push({
                    sampleDuration: e.duration,
                    sampleSize: e.size,
                    sampleFlags: e.sync ? 33554432 : 16842752,
                    sampleCompositionTimeOffset: e.presentationOffset
                })
            }
            const a = {
                type: "moof",
                mfhd: {sequenceNumber: i.fragmentSequence++},
                trafs: [{
                    tfhd: {flags: 131072, trackId: i.trackId},
                    tfdt: {baseMediaDecodeTime: i.samples[r].dts},
                    trun: {flags: 3841, dataOffset: 8, entries: s, version: o}
                }]
            };
            return a.trafs[0].trun.dataOffset += t.encodingLength(a), a
        }
    }
}).call(this)
}).
call(this, y({}).Buffer);
var Cs = {};

function xs(e, t, r = {}) {
    if (!(this instanceof xs)) return console.warn("Don't invoke VideoStream without the 'new' keyword."), new xs(e, t, r);
    this.detailedError = null, this._elem = t, this._elemWrapper = new os(t), this._waitingFired = !1, this._trackMeta = null, this._file = e, this._tracks = null, "none" !== this._elem.preload && this._createMuxer(), this._onError = () => {
        this.detailedError = this._elemWrapper.detailedError, this.destroy()
    }, this._onWaiting = () => {
        this._waitingFired = !0, this._muxer ? this._tracks && this._pump() : this._createMuxer()
    }, t.autoplay && (t.preload = "auto"), t.addEventListener("waiting", this._onWaiting), t.addEventListener("error", this._onError)
}

xs.prototype = {
    _createMuxer() {
        this._muxer = new Ss(this._file), this._muxer.on("ready", e => {
            this._tracks = e.map(e => {
                const t = this._elemWrapper.createWriteStream(e.mime);
                t.on("error", e => {
                    this._elemWrapper.error(e)
                });
                const r = {muxed: null, mediaSource: t, initFlushed: !1, onInitFlushed: null};
                return t.write(e.init, e => {
                    r.initFlushed = !0, r.onInitFlushed && r.onInitFlushed(e)
                }), r
            }), (this._waitingFired || "auto" === this._elem.preload) && this._pump()
        }), this._muxer.on("error", e => {
            this._elemWrapper.error(e)
        })
    }, _pump() {
        const e = this._muxer.seek(this._elem.currentTime, !this._tracks);
        this._tracks.forEach((t, r) => {
            const n = () => {
                t.muxed && (t.muxed.destroy(), t.mediaSource = this._elemWrapper.createWriteStream(t.mediaSource), t.mediaSource.on("error", e => {
                    this._elemWrapper.error(e)
                })), t.muxed = e[r], ns(t.muxed, t.mediaSource)
            };
            t.initFlushed ? n() : t.onInitFlushed = e => {
                e ? this._elemWrapper.error(e) : n()
            }
        })
    }, destroy() {
        this.destroyed || (this.destroyed = !0, this._elem.removeEventListener("waiting", this._onWaiting), this._elem.removeEventListener("error", this._onError), this._tracks && this._tracks.forEach(e => {
            e.muxed && e.muxed.destroy()
        }), this._elem.src = "")
    }
}, Cs = xs;
var As = {
    render: function (e, t, r, n) {
        "function" == typeof r && (n = r, r = {}), r || (r = {}), n || (n = () => {
        }), js(e), Fs(r), "string" == typeof t && (t = document.querySelector(t)), Ds(e, n => {
            if (t.nodeName !== n.toUpperCase()) {
                const r = ft.extname(e.name).toLowerCase();
                throw new Error(`Cannot render "${r}" inside a "${t.nodeName.toLowerCase()}" element, expected "${n}"`)
            }
            return "video" !== n && "audio" !== n || zs(t, r), t
        }, r, n)
    }, append: function (e, t, r, n) {
        if ("function" == typeof r && (n = r, r = {}), r || (r = {}), n || (n = () => {
        }), js(e), Fs(r), "string" == typeof t && (t = document.querySelector(t)), t && ("VIDEO" === t.nodeName || "AUDIO" === t.nodeName)) throw new Error("Invalid video/audio node argument. Argument must be root element that video/audio tag will be appended to.");

        function i(e) {
            const r = document.createElement(e);
            return t.appendChild(r), r
        }

        Ds(e, (function (e) {
            return "video" === e || "audio" === e ? function (e) {
                const n = i(e);
                return zs(n, r), t.appendChild(n), n
            }(e) : i(e)
        }), r, (function (e, t) {
            e && t && t.remove(), n(e, t)
        }))
    }, mime: {
        ".3gp": "video/3gpp",
        ".aac": "audio/aac",
        ".aif": "audio/x-aiff",
        ".aiff": "audio/x-aiff",
        ".atom": "application/atom+xml",
        ".avi": "video/x-msvideo",
        ".bmp": "image/bmp",
        ".bz2": "application/x-bzip2",
        ".conf": "text/plain",
        ".css": "text/css",
        ".csv": "text/plain",
        ".diff": "text/x-diff",
        ".doc": "application/msword",
        ".flv": "video/x-flv",
        ".gif": "image/gif",
        ".gz": "application/x-gzip",
        ".htm": "text/html",
        ".html": "text/html",
        ".ico": "image/vnd.microsoft.icon",
        ".ics": "text/calendar",
        ".iso": "application/octet-stream",
        ".jar": "application/java-archive",
        ".jpeg": "image/jpeg",
        ".jpg": "image/jpeg",
        ".js": "application/javascript",
        ".json": "application/json",
        ".less": "text/css",
        ".log": "text/plain",
        ".m3u": "audio/x-mpegurl",
        ".m4a": "audio/x-m4a",
        ".m4b": "audio/mp4",
        ".m4p": "audio/mp4",
        ".m4v": "video/x-m4v",
        ".manifest": "text/cache-manifest",
        ".markdown": "text/x-markdown",
        ".mathml": "application/mathml+xml",
        ".md": "text/x-markdown",
        ".mid": "audio/midi",
        ".midi": "audio/midi",
        ".mov": "video/quicktime",
        ".mp3": "audio/mpeg",
        ".mp4": "video/mp4",
        ".mp4v": "video/mp4",
        ".mpeg": "video/mpeg",
        ".mpg": "video/mpeg",
        ".odp": "application/vnd.oasis.opendocument.presentation",
        ".ods": "application/vnd.oasis.opendocument.spreadsheet",
        ".odt": "application/vnd.oasis.opendocument.text",
        ".oga": "audio/ogg",
        ".ogg": "application/ogg",
        ".pdf": "application/pdf",
        ".png": "image/png",
        ".pps": "application/vnd.ms-powerpoint",
        ".ppt": "application/vnd.ms-powerpoint",
        ".ps": "application/postscript",
        ".psd": "image/vnd.adobe.photoshop",
        ".qt": "video/quicktime",
        ".rar": "application/x-rar-compressed",
        ".rdf": "application/rdf+xml",
        ".rss": "application/rss+xml",
        ".rtf": "application/rtf",
        ".svg": "image/svg+xml",
        ".svgz": "image/svg+xml",
        ".swf": "application/x-shockwave-flash",
        ".tar": "application/x-tar",
        ".tbz": "application/x-bzip-compressed-tar",
        ".text": "text/plain",
        ".tif": "image/tiff",
        ".tiff": "image/tiff",
        ".torrent": "application/x-bittorrent",
        ".ttf": "application/x-font-ttf",
        ".txt": "text/plain",
        ".wav": "audio/wav",
        ".webm": "video/webm",
        ".wma": "audio/x-ms-wma",
        ".wmv": "video/x-ms-wmv",
        ".xls": "application/vnd.ms-excel",
        ".xml": "application/xml",
        ".yaml": "text/yaml",
        ".yml": "text/yaml",
        ".zip": "application/zip"
    }
};
const Ts = Xt("render-media"), Is = [".m4a", ".m4b", ".m4p", ".m4v", ".mp4"], Rs = [".m4v", ".mkv", ".mp4", ".webm"],
    Bs = [].concat(Rs, [".m4a", ".m4b", ".m4p", ".mp3"]), Ls = [".mov", ".ogv"],
    Os = [".aac", ".oga", ".ogg", ".wav", ".flac"], Us = [".bmp", ".gif", ".jpeg", ".jpg", ".png", ".svg"],
    Ps = [".css", ".html", ".js", ".md", ".pdf", ".srt", ".txt"],
    Ms = "undefined" != typeof window && window.MediaSource;

function Ds(e, t, r, n) {
    const i = ft.extname(e.name).toLowerCase();
    let s, o = 0;

    function a() {
        return !("number" == typeof e.length && e.length > r.maxBlobLength && (Ts("File length too large for Blob URL approach: %d (max: %d)", e.length, r.maxBlobLength), l(new Error(`File length too large for Blob URL approach: ${e.length} (max: ${r.maxBlobLength})`)), 1))
    }

    function h(r) {
        a() && (s = t(r), Ns(e, (e, t) => {
            if (e) return l(e);
            s.addEventListener("error", l), s.addEventListener("loadstart", u), s.addEventListener("loadedmetadata", c), s.src = t
        }))
    }

    function u() {
        if (s.removeEventListener("loadstart", u), r.autoplay) {
            const e = s.play();
            void 0 !== e && e.catch(l)
        }
    }

    function c() {
        s.removeEventListener("loadedmetadata", c), n(null, s)
    }

    function d() {
        Ns(e, (e, r) => {
            if (e) return l(e);
            ".pdf" !== i ? ((s = t("iframe")).sandbox = "allow-forms allow-scripts", s.src = r) : ((s = t("object")).setAttribute("typemustmatch", !0), s.setAttribute("type", "application/pdf"), s.setAttribute("data", r)), n(null, s)
        })
    }

    function l(t) {
        t.message = `Error rendering file "${e.name}": ${t.message}`, Ts(t.message), n(t)
    }

    Bs.includes(i) ? function () {
        const r = Rs.includes(i) ? "video" : "audio";

        function n() {
            Ts("Use MediaSource API for " + e.name), f(), s.addEventListener("error", d), s.addEventListener("loadstart", u), s.addEventListener("loadedmetadata", c);
            const t = new os(s).createWriteStream((r = e.name, {
                ".m4a": 'audio/mp4; codecs="mp4a.40.5"',
                ".m4b": 'audio/mp4; codecs="mp4a.40.5"',
                ".m4p": 'audio/mp4; codecs="mp4a.40.5"',
                ".m4v": 'video/mp4; codecs="avc1.640029, mp4a.40.5"',
                ".mkv": 'video/webm; codecs="avc1.640029, mp4a.40.5"',
                ".mp3": "audio/mpeg",
                ".mp4": 'video/mp4; codecs="avc1.640029, mp4a.40.5"',
                ".webm": 'video/webm; codecs="vorbis, vp8"'
            }[ft.extname(r).toLowerCase()]));
            var r;
            e.createReadStream().pipe(t), o && (s.currentTime = o)
        }

        function h() {
            Ts("Use Blob URL for " + e.name), f(), s.addEventListener("error", l), s.addEventListener("loadstart", u), s.addEventListener("loadedmetadata", c), Ns(e, (e, t) => {
                if (e) return l(e);
                s.src = t, o && (s.currentTime = o)
            })
        }

        function d(e) {
            Ts("MediaSource API error: fallback to Blob URL: %o", e.message || e), a() && (s.removeEventListener("error", d), s.removeEventListener("loadedmetadata", c), h())
        }

        function f() {
            s || (s = t(r)).addEventListener("progress", () => {
                o = s.currentTime
            })
        }

        Ms ? Is.includes(i) ? (Ts("Use `videostream` package for " + e.name), f(), s.addEventListener("error", (function e(t) {
            Ts("videostream error: fallback to MediaSource API: %o", t.message || t), s.removeEventListener("error", e), s.removeEventListener("loadedmetadata", c), n()
        })), s.addEventListener("loadstart", u), s.addEventListener("loadedmetadata", c), new Cs(e, s)) : n() : h()
    }() : Ls.includes(i) ? h("video") : Os.includes(i) ? h("audio") : Us.includes(i) ? (s = t("img"), Ns(e, (t, r) => {
        if (t) return l(t);
        s.src = r, s.alt = e.name, n(null, s)
    })) : Ps.includes(i) ? d() : function () {
        Ts('Unknown file extension "%s" - will attempt to render into iframe', i);
        let t = "";
        e.createReadStream({start: 0, end: 1e3}).setEncoding("utf8").on("data", e => {
            t += e
        }).on("end", (function () {
            !function (e) {
                for (var t = 0, r = e.length; t < r; ++t) if (e.charCodeAt(t) > 127) return !1;
                return !0
            }(t) ? (Ts('File extension "%s" appears non-ascii, will not render.', i), n(new Error(`Unsupported file type "${i}": Cannot append to DOM`))) : (Ts('File extension "%s" appears ascii, so will render.', i), d())
        })).on("error", n)
    }()
}

function Ns(e, t) {
    const r = ft.extname(e.name).toLowerCase();
    ds(e.createReadStream(), As.mime[r]).then(e => t(null, e), e => t(e))
}

function js(e) {
    if (null == e) throw new Error("file cannot be null or undefined");
    if ("string" != typeof e.name) throw new Error("missing or invalid file.name property");
    if ("function" != typeof e.createReadStream) throw new Error("missing or invalid file.createReadStream property")
}

function Fs(e) {
    null == e.autoplay && (e.autoplay = !1), null == e.muted && (e.muted = !1), null == e.controls && (e.controls = !0), null == e.maxBlobLength && (e.maxBlobLength = 2e8)
}

function zs(e, t) {
    e.autoplay = !!t.autoplay, e.muted = !!t.muted, e.controls = !!t.controls
}

var Hs = {};
(function (e) {
    (function () {
        Hs = function (t, r, n) {
            n = Ot(n);
            var i = e.alloc(r), s = 0;
            t.on("data", (function (e) {
                e.copy(i, s), s += e.length
            })).on("end", (function () {
                n(null, i)
            })).on("error", n)
        }
    }).call(this)
}).call(this, y({}).Buffer);
const Ws = Xt("webtorrent:file-stream");
var qs = class extends dt.Readable {
    constructor(e, t) {
        super(t), this.destroyed = !1, this._torrent = e._torrent;
        const r = t && t.start || 0, n = t && t.end && t.end < e.length ? t.end : e.length - 1,
            i = e._torrent.pieceLength;
        this._startPiece = (r + e.offset) / i | 0, this._endPiece = (n + e.offset) / i | 0, this._piece = this._startPiece, this._offset = r + e.offset - this._startPiece * i, this._missing = n - r + 1, this._reading = !1, this._notifying = !1, this._criticalLength = Math.min(1048576 / i | 0, 2)
    }

    _read() {
        this._reading || (this._reading = !0, this._notify())
    }

    _notify() {
        if (!this._reading || 0 === this._missing) return;
        if (!this._torrent.bitfield.get(this._piece)) return this._torrent.critical(this._piece, this._piece + this._criticalLength);
        if (this._notifying) return;
        if (this._notifying = !0, this._torrent.destroyed) return this._destroy(new Error("Torrent removed"));
        const e = this._piece;
        this._torrent.store.get(e, (t, r) => {
            if (this._notifying = !1, !this.destroyed) {
                if (Ws("read %s (length %s) (err %s)", e, r.length, t && t.message), t) return this._destroy(t);
                this._offset && (r = r.slice(this._offset), this._offset = 0), this._missing < r.length && (r = r.slice(0, this._missing)), this._missing -= r.length, Ws("pushing buffer of length %s", r.length), this._reading = !1, this.push(r), 0 === this._missing && this.push(null)
            }
        }), this._piece += 1
    }

    destroy(e) {
        this._destroy(null, e)
    }

    _destroy(e, t) {
        this.destroyed || (this.destroyed = !0, this._torrent.destroyed || this._torrent.deselect(this._startPiece, this._endPiece, !0), e && this.emit("error", e), this.emit("close"), t && t())
    }
}, Zs = {};
(function (e) {
    (function () {
        const {EventEmitter: t} = $, {PassThrough: r} = dt;
        Zs = class extends t {
            constructor(e, t) {
                super(), this._torrent = e, this._destroyed = !1, this.name = t.name, this.path = t.path, this.length = t.length, this.offset = t.offset, this.done = !1;
                const r = t.offset, n = r + t.length - 1;
                this._startPiece = r / this._torrent.pieceLength | 0, this._endPiece = n / this._torrent.pieceLength | 0, 0 === this.length && (this.done = !0, this.emit("done"))
            }

            get downloaded() {
                if (!this._torrent.bitfield) return 0;
                const {pieces: e, bitfield: t, pieceLength: r} = this._torrent, {_startPiece: n, _endPiece: i} = this,
                    s = e[n], o = this.offset % r;
                let a = t.get(n) ? r - o : Math.max(r - o - s.missing, 0);
                for (let h = n + 1; h <= i; ++h) t.get(h) ? a += r : a += r - e[h].missing;
                return Math.min(a, this.length)
            }

            get progress() {
                return this.length ? this.downloaded / this.length : 0
            }

            select(e) {
                0 !== this.length && this._torrent.select(this._startPiece, this._endPiece, e)
            }

            deselect() {
                0 !== this.length && this._torrent.deselect(this._startPiece, this._endPiece, !1)
            }

            createReadStream(t) {
                if (0 === this.length) {
                    const t = new r;
                    return e.nextTick(() => {
                        t.end()
                    }), t
                }
                const n = new qs(this, t);
                return this._torrent.select(n._startPiece, n._endPiece, !0, () => {
                    n._notify()
                }), rs(n, () => {
                    this._destroyed || this._torrent.destroyed || this._torrent.deselect(n._startPiece, n._endPiece, !0)
                }), n
            }

            getBuffer(e) {
                Hs(this.createReadStream(), this.length, e)
            }

            getBlob(e) {
                if ("undefined" == typeof window) throw new Error("browser-only method");
                cs(this.createReadStream(), this._getMimeType()).then(t => e(null, t), t => e(t))
            }

            getBlobURL(e) {
                if ("undefined" == typeof window) throw new Error("browser-only method");
                ds(this.createReadStream(), this._getMimeType()).then(t => e(null, t), t => e(t))
            }

            appendTo(e, t, r) {
                if ("undefined" == typeof window) throw new Error("browser-only method");
                As.append(this, e, t, r)
            }

            renderTo(e, t, r) {
                if ("undefined" == typeof window) throw new Error("browser-only method");
                As.render(this, e, t, r)
            }

            _getMimeType() {
                return As.mime[ft.extname(this.name).toLowerCase()]
            }

            _destroy() {
                this._destroyed = !0, this._torrent = null
            }
        }
    }).call(this)
}).call(this, _e);
var Vs = function (e, t) {
    if (!(t >= e.length || t < 0)) {
        var r = e.pop();
        if (t < e.length) {
            var n = e[t];
            return e[t] = r, n
        }
        return r
    }
}, $s = {};
(function (e) {
    (function () {
        const t = Di.default, r = Xt("bittorrent-protocol"), n = e.from("\x13BitTorrent protocol"),
            i = e.from([0, 0, 0, 0]), s = e.from([0, 0, 0, 1, 0]), o = e.from([0, 0, 0, 1, 1]),
            a = e.from([0, 0, 0, 1, 2]), h = e.from([0, 0, 0, 1, 3]), u = [0, 0, 0, 0, 0, 0, 0, 0],
            c = [0, 0, 0, 3, 9, 0, 0];

        class d {
            constructor(e, t, r, n) {
                this.piece = e, this.offset = t, this.length = r, this.callback = n
            }
        }

        $s = class extends dt.Duplex {
            constructor() {
                super(), this._debugId = bi(4).toString("hex"), this._debug("new wire"), this.peerId = null, this.peerIdBuffer = null, this.type = null, this.amChoking = !0, this.amInterested = !1, this.peerChoking = !0, this.peerInterested = !1, this.peerPieces = new t(0, {grow: 4e5}), this.peerExtensions = {}, this.requests = [], this.peerRequests = [], this.extendedMapping = {}, this.peerExtendedMapping = {}, this.extendedHandshake = {}, this.peerExtendedHandshake = {}, this._ext = {}, this._nextExt = 1, this.uploaded = 0, this.downloaded = 0, this.uploadSpeed = Li(), this.downloadSpeed = Li(), this._keepAliveInterval = null, this._timeout = null, this._timeoutMs = 0, this.destroyed = !1, this._finished = !1, this._parserSize = 0, this._parser = null, this._buffer = [], this._bufferSize = 0, this.once("finish", () => this._onFinish()), this._parseHandshake()
            }

            setKeepAlive(e) {
                this._debug("setKeepAlive %s", e), clearInterval(this._keepAliveInterval), !1 !== e && (this._keepAliveInterval = setInterval(() => {
                    this.keepAlive()
                }, 55e3))
            }

            setTimeout(e, t) {
                this._debug("setTimeout ms=%d unref=%s", e, t), this._clearTimeout(), this._timeoutMs = e, this._timeoutUnref = !!t, this._updateTimeout()
            }

            destroy() {
                this.destroyed || (this.destroyed = !0, this._debug("destroy"), this.emit("close"), this.end())
            }

            end(...e) {
                this._debug("end"), this._onUninterested(), this._onChoke(), super.end(...e)
            }

            use(e) {
                const t = e.prototype.name;
                if (!t) throw new Error('Extension class requires a "name" property on the prototype');
                this._debug("use extension.name=%s", t);
                const r = this._nextExt, n = new e(this);

                function i() {
                }

                "function" != typeof n.onHandshake && (n.onHandshake = i), "function" != typeof n.onExtendedHandshake && (n.onExtendedHandshake = i), "function" != typeof n.onMessage && (n.onMessage = i), this.extendedMapping[r] = t, this._ext[t] = n, this[t] = n, this._nextExt += 1
            }

            keepAlive() {
                this._debug("keep-alive"), this._push(i)
            }

            handshake(t, r, i) {
                let s, o;
                if ("string" == typeof t ? (t = t.toLowerCase(), s = e.from(t, "hex")) : t = (s = t).toString("hex"), "string" == typeof r ? o = e.from(r, "hex") : r = (o = r).toString("hex"), 20 !== s.length || 20 !== o.length) throw new Error("infoHash and peerId MUST have length 20");
                this._debug("handshake i=%s p=%s exts=%o", t, r, i);
                const a = e.from(u);
                a[5] |= 16, i && i.dht && (a[7] |= 1), this._push(e.concat([n, a, s, o])), this._handshakeSent = !0, this.peerExtensions.extended && !this._extendedHandshakeSent && this._sendExtendedHandshake()
            }

            _sendExtendedHandshake() {
                const e = Object.assign({}, this.extendedHandshake);
                e.m = {};
                for (const t in this.extendedMapping) {
                    const r = this.extendedMapping[t];
                    e.m[r] = Number(t)
                }
                this.extended(0, q.encode(e)), this._extendedHandshakeSent = !0
            }

            choke() {
                if (!this.amChoking) {
                    for (this.amChoking = !0, this._debug("choke"); this.peerRequests.length;) this.peerRequests.pop();
                    this._push(s)
                }
            }

            unchoke() {
                this.amChoking && (this.amChoking = !1, this._debug("unchoke"), this._push(o))
            }

            interested() {
                this.amInterested || (this.amInterested = !0, this._debug("interested"), this._push(a))
            }

            uninterested() {
                this.amInterested && (this.amInterested = !1, this._debug("uninterested"), this._push(h))
            }

            have(e) {
                this._debug("have %d", e), this._message(4, [e], null)
            }

            bitfield(t) {
                this._debug("bitfield"), e.isBuffer(t) || (t = t.buffer), this._message(5, [], t)
            }

            request(e, t, r, n) {
                return n || (n = () => {
                }), this._finished ? n(new Error("wire is closed")) : this.peerChoking ? n(new Error("peer is choking")) : (this._debug("request index=%d offset=%d length=%d", e, t, r), this.requests.push(new d(e, t, r, n)), this._updateTimeout(), void this._message(6, [e, t, r], null))
            }

            piece(e, t, r) {
                this._debug("piece index=%d offset=%d", e, t), this.uploaded += r.length, this.uploadSpeed(r.length), this.emit("upload", r.length), this._message(7, [e, t], r)
            }

            cancel(e, t, r) {
                this._debug("cancel index=%d offset=%d length=%d", e, t, r), this._callback(this._pull(this.requests, e, t, r), new Error("request was cancelled"), null), this._message(8, [e, t, r], null)
            }

            port(t) {
                this._debug("port %d", t);
                const r = e.from(c);
                r.writeUInt16BE(t, 5), this._push(r)
            }

            extended(t, r) {
                if (this._debug("extended ext=%s", t), "string" == typeof t && this.peerExtendedMapping[t] && (t = this.peerExtendedMapping[t]), "number" != typeof t) throw new Error("Unrecognized extension: " + t);
                {
                    const n = e.from([t]), i = e.isBuffer(r) ? r : q.encode(r);
                    this._message(20, [], e.concat([n, i]))
                }
            }

            _read() {
            }

            _message(t, r, n) {
                const i = n ? n.length : 0, s = e.allocUnsafe(5 + 4 * r.length);
                s.writeUInt32BE(s.length + i - 4, 0), s[4] = t;
                for (let e = 0; e < r.length; e++) s.writeUInt32BE(r[e], 5 + 4 * e);
                this._push(s), n && this._push(n)
            }

            _push(e) {
                if (!this._finished) return this.push(e)
            }

            _onKeepAlive() {
                this._debug("got keep-alive"), this.emit("keep-alive")
            }

            _onHandshake(e, t, r) {
                const n = e.toString("hex"), i = t.toString("hex");
                let s;
                for (s in this._debug("got handshake i=%s p=%s exts=%o", n, i, r), this.peerId = i, this.peerIdBuffer = t, this.peerExtensions = r, this.emit("handshake", n, i, r), this._ext) this._ext[s].onHandshake(n, i, r);
                r.extended && this._handshakeSent && !this._extendedHandshakeSent && this._sendExtendedHandshake()
            }

            _onChoke() {
                for (this.peerChoking = !0, this._debug("got choke"), this.emit("choke"); this.requests.length;) this._callback(this.requests.pop(), new Error("peer is choking"), null)
            }

            _onUnchoke() {
                this.peerChoking = !1, this._debug("got unchoke"), this.emit("unchoke")
            }

            _onInterested() {
                this.peerInterested = !0, this._debug("got interested"), this.emit("interested")
            }

            _onUninterested() {
                this.peerInterested = !1, this._debug("got uninterested"), this.emit("uninterested")
            }

            _onHave(e) {
                this.peerPieces.get(e) || (this._debug("got have %d", e), this.peerPieces.set(e, !0), this.emit("have", e))
            }

            _onBitField(e) {
                this.peerPieces = new t(e), this._debug("got bitfield"), this.emit("bitfield", this.peerPieces)
            }

            _onRequest(e, t, r) {
                if (this.amChoking) return;
                this._debug("got request index=%d offset=%d length=%d", e, t, r);
                const n = (n, s) => {
                    if (i === this._pull(this.peerRequests, e, t, r)) return n ? this._debug("error satisfying request index=%d offset=%d length=%d (%s)", e, t, r, n.message) : void this.piece(e, t, s)
                }, i = new d(e, t, r, n);
                this.peerRequests.push(i), this.emit("request", e, t, r, n)
            }

            _onPiece(e, t, r) {
                this._debug("got piece index=%d offset=%d", e, t), this._callback(this._pull(this.requests, e, t, r.length), null, r), this.downloaded += r.length, this.downloadSpeed(r.length), this.emit("download", r.length), this.emit("piece", e, t, r)
            }

            _onCancel(e, t, r) {
                this._debug("got cancel index=%d offset=%d length=%d", e, t, r), this._pull(this.peerRequests, e, t, r), this.emit("cancel", e, t, r)
            }

            _onPort(e) {
                this._debug("got port %d", e), this.emit("port", e)
            }

            _onExtended(e, t) {
                if (0 === e) {
                    let e, r;
                    try {
                        e = q.decode(t)
                    } catch (Ga) {
                        this._debug("ignoring invalid extended handshake: %s", Ga.message || Ga)
                    }
                    if (!e) return;
                    if (this.peerExtendedHandshake = e, "object" == typeof e.m) for (r in e.m) this.peerExtendedMapping[r] = Number(e.m[r].toString());
                    for (r in this._ext) this.peerExtendedMapping[r] && this._ext[r].onExtendedHandshake(this.peerExtendedHandshake);
                    this._debug("got extended handshake"), this.emit("extended", "handshake", this.peerExtendedHandshake)
                } else this.extendedMapping[e] && (e = this.extendedMapping[e], this._ext[e] && this._ext[e].onMessage(t)), this._debug("got extended message ext=%s", e), this.emit("extended", e, t)
            }

            _onTimeout() {
                this._debug("request timed out"), this._callback(this.requests.shift(), new Error("request has timed out"), null), this.emit("timeout")
            }

            _write(t, r, n) {
                for (this._bufferSize += t.length, this._buffer.push(t); this._bufferSize >= this._parserSize;) {
                    const t = 1 === this._buffer.length ? this._buffer[0] : e.concat(this._buffer);
                    this._bufferSize -= this._parserSize, this._buffer = this._bufferSize ? [t.slice(this._parserSize)] : [], this._parser(t.slice(0, this._parserSize))
                }
                n(null)
            }

            _callback(e, t, r) {
                e && (this._clearTimeout(), this.peerChoking || this._finished || this._updateTimeout(), e.callback(t, r))
            }

            _clearTimeout() {
                this._timeout && (clearTimeout(this._timeout), this._timeout = null)
            }

            _updateTimeout() {
                this._timeoutMs && this.requests.length && !this._timeout && (this._timeout = setTimeout(() => this._onTimeout(), this._timeoutMs), this._timeoutUnref && this._timeout.unref && this._timeout.unref())
            }

            _parse(e, t) {
                this._parserSize = e, this._parser = t
            }

            _onMessageLength(e) {
                const t = e.readUInt32BE(0);
                t > 0 ? this._parse(t, this._onMessage) : (this._onKeepAlive(), this._parse(4, this._onMessageLength))
            }

            _onMessage(e) {
                switch (this._parse(4, this._onMessageLength), e[0]) {
                    case 0:
                        return this._onChoke();
                    case 1:
                        return this._onUnchoke();
                    case 2:
                        return this._onInterested();
                    case 3:
                        return this._onUninterested();
                    case 4:
                        return this._onHave(e.readUInt32BE(1));
                    case 5:
                        return this._onBitField(e.slice(1));
                    case 6:
                        return this._onRequest(e.readUInt32BE(1), e.readUInt32BE(5), e.readUInt32BE(9));
                    case 7:
                        return this._onPiece(e.readUInt32BE(1), e.readUInt32BE(5), e.slice(9));
                    case 8:
                        return this._onCancel(e.readUInt32BE(1), e.readUInt32BE(5), e.readUInt32BE(9));
                    case 9:
                        return this._onPort(e.readUInt16BE(1));
                    case 20:
                        return this._onExtended(e.readUInt8(1), e.slice(2));
                    default:
                        return this._debug("got unknown message"), this.emit("unknownmessage", e)
                }
            }

            _parseHandshake() {
                this._parse(1, e => {
                    const t = e.readUInt8(0);
                    this._parse(t + 48, e => {
                        const r = e.slice(0, t);
                        if ("BitTorrent protocol" !== r.toString()) return this._debug("Error: wire not speaking BitTorrent protocol (%s)", r.toString()), void this.end();
                        e = e.slice(t), this._onHandshake(e.slice(8, 28), e.slice(28, 48), {
                            dht: !!(1 & e[7]),
                            extended: !!(16 & e[5])
                        }), this._parse(4, this._onMessageLength)
                    })
                })
            }

            _onFinish() {
                for (this._finished = !0, this.push(null); this.read();) ;
                for (clearInterval(this._keepAliveInterval), this._parse(Number.MAX_VALUE, () => {
                }); this.peerRequests.length;) this.peerRequests.pop();
                for (; this.requests.length;) this._callback(this.requests.pop(), new Error("wire was closed"), null)
            }

            _debug(...e) {
                e[0] = `[${this._debugId}] ${e[0]}`, r(...e)
            }

            _pull(e, t, r, n) {
                for (let i = 0; i < e.length; i++) {
                    const s = e[i];
                    if (s.piece === t && s.offset === r && s.length === n) return Vs(e, i), s
                }
                return null
            }
        }
    }).call(this)
}).call(this, y({}).Buffer);
var Ks = "0.112.0", Gs = {};
(function (e) {
    (function () {
        const t = Di.default, r = Xt("webtorrent:webconn"), n = Ks;
        Gs = class extends $s {
            constructor(e, t) {
                super(), this.url = e, this.webPeerId = Wt.sync(e), this._torrent = t, this._init()
            }

            _init() {
                this.setKeepAlive(!0), this.once("handshake", (e, r) => {
                    if (this.destroyed) return;
                    this.handshake(e, this.webPeerId);
                    const n = this._torrent.pieces.length, i = new t(n);
                    for (let t = 0; t <= n; t++) i.set(t, !0);
                    this.bitfield(i)
                }), this.once("interested", () => {
                    r("interested"), this.unchoke()
                }), this.on("uninterested", () => {
                    r("uninterested")
                }), this.on("choke", () => {
                    r("choke")
                }), this.on("unchoke", () => {
                    r("unchoke")
                }), this.on("bitfield", () => {
                    r("bitfield")
                }), this.on("request", (e, t, n, i) => {
                    r("request pieceIndex=%d offset=%d length=%d", e, t, n), this.httpRequest(e, t, n, i)
                })
            }

            httpRequest(t, i, s, o) {
                const a = t * this._torrent.pieceLength + i, h = a + s - 1, u = this._torrent.files;
                let c;
                if (u.length <= 1) c = [{url: this.url, start: a, end: h}]; else {
                    const e = u.filter(e => e.offset <= h && e.offset + e.length > a);
                    if (e.length < 1) return o(new Error("Could not find file corresponnding to web seed range request"));
                    c = e.map(e => {
                        const t = e.offset + e.length - 1;
                        return {
                            url: this.url + ("/" === this.url[this.url.length - 1] ? "" : "/") + e.path,
                            fileOffsetInRange: Math.max(e.offset - a, 0),
                            start: Math.max(a - e.offset, 0),
                            end: Math.min(t, h - e.offset)
                        }
                    })
                }
                let d, l = 0, f = !1;
                c.length > 1 && (d = e.alloc(s)), c.forEach(e => {
                    const a = e.url, h = e.start, u = e.end;
                    r("Requesting url=%s pieceIndex=%d offset=%d length=%d start=%d end=%d", a, t, i, s, h, u);
                    const p = {
                        url: a,
                        method: "GET",
                        headers: {"user-agent": `WebTorrent/${n} (https://webtorrent.io)`, range: `bytes=${h}-${u}`}
                    };

                    function m(t, n) {
                        if (t.statusCode < 200 || t.statusCode >= 300) return f = !0, o(new Error("Unexpected HTTP status code " + t.statusCode));
                        r("Got data of length %d", n.length), 1 === c.length ? o(null, n) : (n.copy(d, e.fileOffsetInRange), ++l === c.length && o(null, d))
                    }

                    Nr.concat(p, (e, t, r) => {
                        if (!f) return e ? "undefined" == typeof window || a.startsWith(window.location.origin + "/") ? (f = !0, o(e)) : Nr.head(a, (t, r) => {
                            if (!f) {
                                if (t) return f = !0, o(t);
                                if (r.statusCode < 200 || r.statusCode >= 300) return f = !0, o(new Error("Unexpected HTTP status code " + r.statusCode));
                                if (r.url === a) return f = !0, o(e);
                                p.url = r.url, Nr.concat(p, (e, t, r) => {
                                    if (!f) return e ? (f = !0, o(e)) : void m(t, r)
                                })
                            }
                        }) : void m(t, r)
                    })
                })
            }

            destroy() {
                super.destroy(), this._torrent = null
            }
        }
    }).call(this)
}).call(this, y({}).Buffer);
var Xs = {};
const Ys = Xt("webtorrent:peer");
Xs.createWebRTCPeer = (e, t) => {
    const r = new Qs(e.id, "webrtc");
    return r.conn = e, r.swarm = t, r.conn.connected ? r.onConnect() : (r.conn.once("connect", () => {
        r.onConnect()
    }), r.conn.once("error", e => {
        r.destroy(e)
    }), r.startConnectTimeout()), r
}, Xs.createTCPOutgoingPeer = (e, t) => Js(e, t, "tcpOutgoing"), Xs.createUTPOutgoingPeer = (e, t) => Js(e, t, "utpOutgoing");
const Js = (e, t, r) => {
    const n = new Qs(e, r);
    return n.addr = e, n.swarm = t, n
};
Xs.createWebSeedPeer = (e, t) => {
    const r = new Qs(e, "webSeed");
    return r.swarm = t, r.conn = new Gs(e, t), r.onConnect(), r
};

class Qs {
    constructor(e, t) {
        this.id = e, this.type = t, Ys("new %s Peer %s", t, e), this.addr = null, this.conn = null, this.swarm = null, this.wire = null, this.connected = !1, this.destroyed = !1, this.timeout = null, this.retries = 0, this.sentHandshake = !1
    }

    onConnect() {
        if (this.destroyed) return;
        this.connected = !0, Ys("Peer %s connected", this.id), clearTimeout(this.connectTimeout);
        const e = this.conn;
        e.once("end", () => {
            this.destroy()
        }), e.once("close", () => {
            this.destroy()
        }), e.once("finish", () => {
            this.destroy()
        }), e.once("error", e => {
            this.destroy(e)
        });
        const t = this.wire = new $s;
        t.type = this.type, t.once("end", () => {
            this.destroy()
        }), t.once("close", () => {
            this.destroy()
        }), t.once("finish", () => {
            this.destroy()
        }), t.once("error", e => {
            this.destroy(e)
        }), t.once("handshake", (e, t) => {
            this.onHandshake(e, t)
        }), this.startHandshakeTimeout(), e.pipe(t).pipe(e), this.swarm && !this.sentHandshake && this.handshake()
    }

    onHandshake(e, t) {
        if (!this.swarm) return;
        if (this.destroyed) return;
        if (this.swarm.destroyed) return this.destroy(new Error("swarm already destroyed"));
        if (e !== this.swarm.infoHash) return this.destroy(new Error("unexpected handshake info hash for this swarm"));
        if (t === this.swarm.peerId) return this.destroy(new Error("refusing to connect to ourselves"));
        Ys("Peer %s got handshake %s", this.id, e), clearTimeout(this.handshakeTimeout), this.retries = 0;
        let r = this.addr;
        !r && this.conn.remoteAddress && this.conn.remotePort && (r = `${this.conn.remoteAddress}:${this.conn.remotePort}`), this.swarm._onWire(this.wire, r), this.swarm && !this.swarm.destroyed && (this.sentHandshake || this.handshake())
    }

    handshake() {
        const e = {dht: !this.swarm.private && !!this.swarm.client.dht};
        this.wire.handshake(this.swarm.infoHash, this.swarm.client.peerId, e), this.sentHandshake = !0
    }

    startConnectTimeout() {
        clearTimeout(this.connectTimeout);
        const e = {webrtc: 25e3, tcpOutgoing: 5e3, utpOutgoing: 5e3};
        this.connectTimeout = setTimeout(() => {
            this.destroy(new Error("connect timeout"))
        }, e[this.type]), this.connectTimeout.unref && this.connectTimeout.unref()
    }

    startHandshakeTimeout() {
        clearTimeout(this.handshakeTimeout), this.handshakeTimeout = setTimeout(() => {
            this.destroy(new Error("handshake timeout"))
        }, 25e3), this.handshakeTimeout.unref && this.handshakeTimeout.unref()
    }

    destroy(e) {
        if (this.destroyed) return;
        this.destroyed = !0, this.connected = !1, Ys("destroy %s %s (error: %s)", this.type, this.id, e && (e.message || e)), clearTimeout(this.connectTimeout), clearTimeout(this.handshakeTimeout);
        const t = this.swarm, r = this.conn, n = this.wire;
        this.swarm = null, this.conn = null, this.wire = null, t && n && Vs(t.wires, t.wires.indexOf(n)), r && (r.on("error", () => {
        }), r.destroy()), n && n.destroy(), t && t.removePeer(this.id)
    }
}

var eo = {};
(function (e, t) {
    (function () {
        const r = Di.default, n = Xt("webtorrent:torrent"), i = $.EventEmitter, s = 3 * Qi.BLOCK_LENGTH,
            o = e.browser ? 1 / 0 : 2, a = [1e3, 5e3, 15e3], h = `WebTorrent/${Ks} (https://webtorrent.io)`;
        let u;
        try {
            u = ft.join(St.statSync("/tmp") && "/tmp", "webtorrent")
        } catch (Ga) {
            u = ft.join("function" == typeof ae.tmpdir ? ae.tmpdir() : "/", "webtorrent")
        }

        function c(e, t) {
            return 2 + Math.ceil(t * e.downloadSpeed() / Qi.BLOCK_LENGTH)
        }

        function d() {
        }

        eo = class extends i {
            constructor(e, t, r) {
                super(), this._debugId = "unknown infohash", this.client = t, this.announce = r.announce, this.urlList = r.urlList, this.path = r.path, this.skipVerify = !!r.skipVerify, this._store = r.store || Yi, this._getAnnounceOpts = r.getAnnounceOpts, "boolean" == typeof r.private && (this.private = r.private), this.strategy = r.strategy || "sequential", this.maxWebConns = r.maxWebConns || 4, this._rechokeNumSlots = !1 === r.uploads || 0 === r.uploads ? 0 : +r.uploads || 10, this._rechokeOptimisticWire = null, this._rechokeOptimisticTime = 0, this._rechokeIntervalId = null, this.ready = !1, this.destroyed = !1, this.paused = !1, this.done = !1, this.metadata = null, this.store = null, this.files = [], this.pieces = [], this._amInterested = !1, this._selections = [], this._critical = [], this.wires = [], this._queue = [], this._peers = {}, this._peersLength = 0, this.received = 0, this.uploaded = 0, this._downloadSpeed = Li(), this._uploadSpeed = Li(), this._servers = [], this._xsRequests = [], this._fileModtimes = r.fileModtimes, null !== e && this._onTorrentId(e), this._debug("new torrent")
            }

            get timeRemaining() {
                return this.done ? 0 : 0 === this.downloadSpeed ? 1 / 0 : (this.length - this.downloaded) / this.downloadSpeed * 1e3
            }

            get downloaded() {
                if (!this.bitfield) return 0;
                let e = 0;
                for (let t = 0, r = this.pieces.length; t < r; ++t) if (this.bitfield.get(t)) e += t === r - 1 ? this.lastPieceLength : this.pieceLength; else {
                    const r = this.pieces[t];
                    e += r.length - r.missing
                }
                return e
            }

            get downloadSpeed() {
                return this._downloadSpeed()
            }

            get uploadSpeed() {
                return this._uploadSpeed()
            }

            get progress() {
                return this.length ? this.downloaded / this.length : 0
            }

            get ratio() {
                return this.uploaded / (this.received || this.length)
            }

            get numPeers() {
                return this.wires.length
            }

            get torrentFileBlobURL() {
                if ("undefined" == typeof window) throw new Error("browser-only property");
                return this.torrentFile ? URL.createObjectURL(new Blob([this.torrentFile], {type: "application/x-bittorrent"})) : null
            }

            get _numQueued() {
                return this._queue.length + (this._peersLength - this._numConns)
            }

            get _numConns() {
                let e = 0;
                for (const t in this._peers) this._peers[t].connected && (e += 1);
                return e
            }

            get swarm() {
                return console.warn("WebTorrent: `torrent.swarm` is deprecated. Use `torrent` directly instead."), this
            }

            _onTorrentId(t) {
                if (this.destroyed) return;
                let r;
                try {
                    r = _i(t)
                } catch (Ga) {
                }
                r ? (this.infoHash = r.infoHash, this._debugId = r.infoHash.toString("hex").substring(0, 7), e.nextTick(() => {
                    this.destroyed || this._onParsedTorrent(r)
                })) : _i.remote(t, (e, t) => {
                    if (!this.destroyed) return e ? this._destroy(e) : void this._onParsedTorrent(t)
                })
            }

            _onParsedTorrent(e) {
                if (!this.destroyed) {
                    if (this._processParsedTorrent(e), !this.infoHash) return this._destroy(new Error("Malformed torrent data: No info hash"));
                    this.path || (this.path = ft.join(u, this.infoHash)), this._rechokeIntervalId = setInterval(() => {
                        this._rechoke()
                    }, 1e4), this._rechokeIntervalId.unref && this._rechokeIntervalId.unref(), this.emit("_infoHash", this.infoHash), this.destroyed || (this.emit("infoHash", this.infoHash), this.destroyed || (this.client.listening ? this._onListening() : this.client.once("listening", () => {
                        this._onListening()
                    })))
                }
            }

            _processParsedTorrent(e) {
                this._debugId = e.infoHash.toString("hex").substring(0, 7), void 0 !== this.private && (e.private = this.private), this.announce && (e.announce = e.announce.concat(this.announce)), this.client.tracker && t.WEBTORRENT_ANNOUNCE && !e.private && (e.announce = e.announce.concat(t.WEBTORRENT_ANNOUNCE)), this.urlList && (e.urlList = e.urlList.concat(this.urlList)), e.announce = Array.from(new Set(e.announce)), e.urlList = Array.from(new Set(e.urlList)), Object.assign(this, e), this.magnetURI = _i.toMagnetURI(e), this.torrentFile = _i.toTorrentFile(e)
            }

            _onListening() {
                this.destroyed || (this.info ? this._onMetadata(this) : (this.xs && this._getMetadataFromServer(), this._startDiscovery()))
            }

            _startDiscovery() {
                if (this.discovery || this.destroyed) return;
                let e = this.client.tracker;
                e && (e = Object.assign({}, this.client.tracker, {
                    getAnnounceOpts: () => {
                        const e = {
                            uploaded: this.uploaded,
                            downloaded: this.downloaded,
                            left: Math.max(this.length - this.downloaded, 0)
                        };
                        return this.client.tracker.getAnnounceOpts && Object.assign(e, this.client.tracker.getAnnounceOpts()), this._getAnnounceOpts && Object.assign(e, this._getAnnounceOpts()), e
                    }
                })), this.peerAddresses && this.peerAddresses.forEach(e => this.addPeer(e)), this.discovery = new Xi({
                    infoHash: this.infoHash,
                    announce: this.announce,
                    peerId: this.client.peerId,
                    dht: !this.private && this.client.dht,
                    tracker: e,
                    port: this.client.torrentPort,
                    userAgent: h,
                    lsd: this.client.lsd
                }), this.discovery.on("error", e => {
                    this._destroy(e)
                }), this.discovery.on("peer", (e, t) => {
                    this._debug("peer %s discovered via %s", e, t), "string" == typeof e && this.done || this.addPeer(e)
                }), this.discovery.on("trackerAnnounce", () => {
                    this.emit("trackerAnnounce"), 0 === this.numPeers && this.emit("noPeers", "tracker")
                }), this.discovery.on("dhtAnnounce", () => {
                    this.emit("dhtAnnounce"), 0 === this.numPeers && this.emit("noPeers", "dht")
                }), this.discovery.on("warning", e => {
                    this.emit("warning", e)
                })
            }

            _getMetadataFromServer() {
                const e = this, t = (Array.isArray(this.xs) ? this.xs : [this.xs]).map(t => r => {
                    !function (t, r) {
                        if (0 !== t.indexOf("http://") && 0 !== t.indexOf("https://")) return e.emit("warning", new Error("skipping non-http xs param: " + t)), r(null);
                        const n = {url: t, method: "GET", headers: {"user-agent": h}};
                        let i;
                        try {
                            i = Nr.concat(n, (function (n, i, s) {
                                if (e.destroyed) return r(null);
                                if (e.metadata) return r(null);
                                if (n) return e.emit("warning", new Error("http error from xs param: " + t)), r(null);
                                if (200 !== i.statusCode) return e.emit("warning", new Error(`non-200 status code ${i.statusCode} from xs param: ${t}`)), r(null);
                                let o;
                                try {
                                    o = _i(s)
                                } catch (n) {
                                }
                                return o ? o.infoHash !== e.infoHash ? (e.emit("warning", new Error("got torrent file with incorrect info hash from xs param: " + t)), r(null)) : (e._onMetadata(o), void r(null)) : (e.emit("warning", new Error("got invalid torrent file from xs param: " + t)), r(null))
                            }))
                        } catch (Ga) {
                            return e.emit("warning", new Error("skipping invalid url xs param: " + t)), r(null)
                        }
                        e._xsRequests.push(i)
                    }(t, r)
                });
                Mt(t)
            }

            _onMetadata(e) {
                if (this.metadata || this.destroyed) return;
                let t;
                if (this._debug("got metadata"), this._xsRequests.forEach(e => {
                    e.abort()
                }), this._xsRequests = [], e && e.infoHash) t = e; else try {
                    t = _i(e)
                } catch (Ga) {
                    return this._destroy(Ga)
                }
                if (this._processParsedTorrent(t), this.metadata = this.torrentFile, this.client.enableWebSeeds && this.urlList.forEach(e => {
                    this.addWebSeed(e)
                }), this._rarityMap = new class {
                    constructor(e) {
                        this._torrent = e, this._numPieces = e.pieces.length, this._pieces = new Array(this._numPieces), this._onWire = e => {
                            this.recalculate(), this._initWire(e)
                        }, this._onWireHave = e => {
                            this._pieces[e] += 1
                        }, this._onWireBitfield = () => {
                            this.recalculate()
                        }, this._torrent.wires.forEach(e => {
                            this._initWire(e)
                        }), this._torrent.on("wire", this._onWire), this.recalculate()
                    }

                    getRarestPiece(e) {
                        let t = [], r = 1 / 0;
                        for (let n = 0; n < this._numPieces; ++n) {
                            if (e && !e(n)) continue;
                            const i = this._pieces[n];
                            i === r ? t.push(n) : i < r && (t = [n], r = i)
                        }
                        return t.length ? t[Math.random() * t.length | 0] : -1
                    }

                    destroy() {
                        this._torrent.removeListener("wire", this._onWire), this._torrent.wires.forEach(e => {
                            this._cleanupWireEvents(e)
                        }), this._torrent = null, this._pieces = null, this._onWire = null, this._onWireHave = null, this._onWireBitfield = null
                    }

                    _initWire(e) {
                        e._onClose = () => {
                            this._cleanupWireEvents(e);
                            for (let t = 0; t < this._numPieces; ++t) this._pieces[t] -= e.peerPieces.get(t)
                        }, e.on("have", this._onWireHave), e.on("bitfield", this._onWireBitfield), e.once("close", e._onClose)
                    }

                    recalculate() {
                        this._pieces.fill(0);
                        for (const e of this._torrent.wires) for (let t = 0; t < this._numPieces; ++t) this._pieces[t] += e.peerPieces.get(t)
                    }

                    _cleanupWireEvents(e) {
                        e.removeListener("have", this._onWireHave), e.removeListener("bitfield", this._onWireBitfield), e._onClose && e.removeListener("close", e._onClose), e._onClose = null
                    }
                }(this), this.store = new class {
                    constructor(e) {
                        if (this.store = e, this.chunkLength = e.chunkLength, !this.store || !this.store.get || !this.store.put) throw new Error("First argument must be abstract-chunk-store compliant");
                        this.mem = []
                    }

                    put(e, t, r) {
                        this.mem[e] = t, this.store.put(e, t, t => {
                            this.mem[e] = null, r && r(t)
                        })
                    }

                    get(e, t, r) {
                        if ("function" == typeof t) return this.get(e, null, t);
                        let n = this.mem[e];
                        if (!n) return this.store.get(e, t, r);
                        if (t) {
                            const e = t.offset || 0, r = t.length ? e + t.length : n.length;
                            n = n.slice(e, r)
                        }
                        wi(() => {
                            r && r(null, n)
                        })
                    }

                    close(e) {
                        this.store.close(e)
                    }

                    destroy(e) {
                        this.store.destroy(e)
                    }
                }(new this._store(this.pieceLength, {
                    torrent: {infoHash: this.infoHash},
                    files: this.files.map(e => ({
                        path: ft.join(this.path, e.path),
                        length: e.length,
                        offset: e.offset
                    })),
                    length: this.length,
                    name: this.infoHash
                })), this.files = this.files.map(e => new Zs(this, e)), this.so ? this.files.forEach((e, t) => {
                    this.so.includes(t) ? this.files[t].select() : this.files[t].deselect()
                }) : 0 !== this.pieces.length && this.select(0, this.pieces.length - 1, !1), this._hashes = this.pieces, this.pieces = this.pieces.map((e, t) => {
                    const r = t === this.pieces.length - 1 ? this.lastPieceLength : this.pieceLength;
                    return new Qi(r)
                }), this._reservations = this.pieces.map(() => []), this.bitfield = new r(this.pieces.length), this.wires.forEach(e => {
                    e.ut_metadata && e.ut_metadata.setMetadata(this.metadata), this._onWireWithMetadata(e)
                }), this.emit("metadata"), !this.destroyed) if (this.skipVerify) this._markAllVerified(), this._onStore(); else {
                    const e = e => {
                        if (e) return this._destroy(e);
                        this._debug("done verifying"), this._onStore()
                    };
                    this._debug("verifying existing torrent data"), this._fileModtimes && this._store === Yi ? this.getFileModtimes((t, r) => {
                        if (t) return this._destroy(t);
                        this.files.map((e, t) => r[t] === this._fileModtimes[t]).every(e => e) ? (this._markAllVerified(), this._onStore()) : this._verifyPieces(e)
                    }) : this._verifyPieces(e)
                }
            }

            getFileModtimes(e) {
                const t = [];
                Ji(this.files.map((e, r) => n => {
                    St.stat(ft.join(this.path, e.path), (e, i) => {
                        if (e && "ENOENT" !== e.code) return n(e);
                        t[r] = i && i.mtime.getTime(), n(null)
                    })
                }), o, r => {
                    this._debug("done getting file modtimes"), e(r, t)
                })
            }

            _verifyPieces(t) {
                Ji(this.pieces.map((t, r) => t => {
                    if (this.destroyed) return t(new Error("torrent is destroyed"));
                    this.store.get(r, (n, i) => this.destroyed ? t(new Error("torrent is destroyed")) : n ? e.nextTick(t, null) : void Wt(i, e => {
                        if (this.destroyed) return t(new Error("torrent is destroyed"));
                        if (e === this._hashes[r]) {
                            if (!this.pieces[r]) return t(null);
                            this._debug("piece verified %s", r), this._markVerified(r)
                        } else this._debug("piece invalid %s", r);
                        t(null)
                    }))
                }), o, t)
            }

            rescanFiles(e) {
                if (this.destroyed) throw new Error("torrent is destroyed");
                e || (e = d), this._verifyPieces(t => {
                    if (t) return this._destroy(t), e(t);
                    this._checkDone(), e(null)
                })
            }

            _markAllVerified() {
                for (let e = 0; e < this.pieces.length; e++) this._markVerified(e)
            }

            _markVerified(e) {
                this.pieces[e] = null, this._reservations[e] = null, this.bitfield.set(e, !0)
            }

            _onStore() {
                this.destroyed || (this._debug("on store"), this._startDiscovery(), this.ready = !0, this.emit("ready"), this._checkDone(), this._updateSelections())
            }

            destroy(e, t) {
                if ("function" == typeof e) return this.destroy(null, e);
                this._destroy(null, e, t)
            }

            _destroy(e, t, r) {
                if ("function" == typeof t) return this._destroy(e, null, t);
                if (this.destroyed) return;
                this.destroyed = !0, this._debug("destroy"), this.client._remove(this), clearInterval(this._rechokeIntervalId), this._xsRequests.forEach(e => {
                    e.abort()
                }), this._rarityMap && this._rarityMap.destroy();
                for (const i in this._peers) this.removePeer(i);
                this.files.forEach(e => {
                    e instanceof Zs && e._destroy()
                });
                const n = this._servers.map(e => t => {
                    e.destroy(t)
                });
                this.discovery && n.push(e => {
                    this.discovery.destroy(e)
                }), this.store && n.push(e => {
                    t && t.destroyStore ? this.store.destroy(e) : this.store.close(e)
                }), Mt(n, r), e && (0 === this.listenerCount("error") ? this.client.emit("error", e) : this.emit("error", e)), this.emit("close"), this.client = null, this.files = [], this.discovery = null, this.store = null, this._rarityMap = null, this._peers = null, this._servers = null, this._xsRequests = null
            }

            addPeer(e) {
                if (this.destroyed) throw new Error("torrent is destroyed");
                if (!this.infoHash) throw new Error("addPeer() must not be called before the `infoHash` event");
                if (this.client.blocked) {
                    let t;
                    if ("string" == typeof e) {
                        let n;
                        try {
                            n = Oi(e)
                        } catch (r) {
                            return this._debug("ignoring peer: invalid %s", e), this.emit("invalidPeer", e), !1
                        }
                        t = n[0]
                    } else "string" == typeof e.remoteAddress && (t = e.remoteAddress);
                    if (t && this.client.blocked.contains(t)) return this._debug("ignoring peer: blocked %s", e), "string" != typeof e && e.destroy(), this.emit("blockedPeer", e), !1
                }
                const t = !!this._addPeer(e, this.client.utp ? "utp" : "tcp");
                return t ? this.emit("peer", e) : this.emit("invalidPeer", e), t
            }

            _addPeer(e, t) {
                if (this.destroyed) return "string" != typeof e && e.destroy(), null;
                if ("string" == typeof e && !this._validAddr(e)) return this._debug("ignoring peer: invalid %s", e), null;
                const r = e && e.id || e;
                if (this._peers[r]) return this._debug("ignoring peer: duplicate (%s)", r), "string" != typeof e && e.destroy(), null;
                if (this.paused) return this._debug("ignoring peer: torrent is paused"), "string" != typeof e && e.destroy(), null;
                let n;
                return this._debug("add peer %s", r), n = "string" == typeof e ? "utp" === t ? Xs.createUTPOutgoingPeer(e, this) : Xs.createTCPOutgoingPeer(e, this) : Xs.createWebRTCPeer(e, this), this._peers[n.id] = n, this._peersLength += 1, "string" == typeof e && (this._queue.push(n), this._drain()), n
            }

            addWebSeed(e) {
                if (this.destroyed) throw new Error("torrent is destroyed");
                if (!/^https?:\/\/.+/.test(e)) return this.emit("warning", new Error("ignoring invalid web seed: " + e)), void this.emit("invalidPeer", e);
                if (this._peers[e]) return this.emit("warning", new Error("ignoring duplicate web seed: " + e)), void this.emit("invalidPeer", e);
                this._debug("add web seed %s", e);
                const t = Xs.createWebSeedPeer(e, this);
                this._peers[t.id] = t, this._peersLength += 1, this.emit("peer", e)
            }

            _addIncomingPeer(e) {
                return this.destroyed ? e.destroy(new Error("torrent is destroyed")) : this.paused ? e.destroy(new Error("torrent is paused")) : (this._debug("add incoming peer %s", e.id), this._peers[e.id] = e, void (this._peersLength += 1))
            }

            removePeer(e) {
                const t = e && e.id || e;
                (e = this._peers[t]) && (this._debug("removePeer %s", t), delete this._peers[t], this._peersLength -= 1, e.destroy(), this._drain())
            }

            select(e, t, r, n) {
                if (this.destroyed) throw new Error("torrent is destroyed");
                if (e < 0 || t < e || this.pieces.length <= t) throw new Error(`invalid selection ${e} : ${t}`);
                r = Number(r) || 0, this._debug("select %s-%s (priority %s)", e, t, r), this._selections.push({
                    from: e,
                    to: t,
                    offset: 0,
                    priority: r,
                    notify: n || d
                }), this._selections.sort((e, t) => t.priority - e.priority), this._updateSelections()
            }

            deselect(e, t, r) {
                if (this.destroyed) throw new Error("torrent is destroyed");
                r = Number(r) || 0, this._debug("deselect %s-%s (priority %s)", e, t, r);
                for (let n = 0; n < this._selections.length; ++n) {
                    const i = this._selections[n];
                    if (i.from === e && i.to === t && i.priority === r) {
                        this._selections.splice(n, 1);
                        break
                    }
                }
                this._updateSelections()
            }

            critical(e, t) {
                if (this.destroyed) throw new Error("torrent is destroyed");
                this._debug("critical %s-%s", e, t);
                for (let r = e; r <= t; ++r) this._critical[r] = !0;
                this._updateSelections()
            }

            _onWire(t, r) {
                if (this._debug("got wire %s (%s)", t._debugId, r || "Unknown"), t.on("download", e => {
                    this.destroyed || (this.received += e, this._downloadSpeed(e), this.client._downloadSpeed(e), this.emit("download", e), this.destroyed || this.client.emit("download", e))
                }), t.on("upload", e => {
                    this.destroyed || (this.uploaded += e, this._uploadSpeed(e), this.client._uploadSpeed(e), this.emit("upload", e), this.destroyed || this.client.emit("upload", e))
                }), this.wires.push(t), r) {
                    const e = Oi(r);
                    t.remoteAddress = e[0], t.remotePort = e[1]
                }
                this.client.dht && this.client.dht.listening && t.on("port", e => {
                    if (!this.destroyed && !this.client.dht.destroyed) {
                        if (!t.remoteAddress) return this._debug("ignoring PORT from peer with no address");
                        if (0 === e || e > 65536) return this._debug("ignoring invalid PORT from peer");
                        this._debug("port: %s (from %s)", e, r), this.client.dht.addNode({
                            host: t.remoteAddress,
                            port: e
                        })
                    }
                }), t.on("timeout", () => {
                    this._debug("wire timeout (%s)", r), t.destroy()
                }), t.setTimeout(3e4, !0), t.setKeepAlive(!0), t.use(is(this.metadata)), t.ut_metadata.on("warning", e => {
                    this._debug("ut_metadata warning: %s", e.message)
                }), this.metadata || (t.ut_metadata.on("metadata", e => {
                    this._debug("got metadata via ut_metadata"), this._onMetadata(e)
                }), t.ut_metadata.fetch()), "function" != typeof ae || this.private || (t.use(ae()), t.ut_pex.on("peer", e => {
                    this.done || (this._debug("ut_pex: got peer: %s (from %s)", e, r), this.addPeer(e))
                }), t.ut_pex.on("dropped", e => {
                    const t = this._peers[e];
                    t && !t.connected && (this._debug("ut_pex: dropped peer: %s (from %s)", e, r), this.removePeer(e))
                }), t.once("close", () => {
                    t.ut_pex.reset()
                })), this.emit("wire", t, r), this.metadata && e.nextTick(() => {
                    this._onWireWithMetadata(t)
                })
            }

            _onWireWithMetadata(e) {
                let t = null;
                const r = () => {
                    this.destroyed || e.destroyed || (this._numQueued > 2 * (this._numConns - this.numPeers) && e.amInterested ? e.destroy() : (t = setTimeout(r, 5e3)).unref && t.unref())
                };
                let n;
                const i = () => {
                    if (e.peerPieces.buffer.length === this.bitfield.buffer.length) {
                        for (n = 0; n < this.pieces.length; ++n) if (!e.peerPieces.get(n)) return;
                        e.isSeeder = !0, e.choke()
                    }
                };
                e.on("bitfield", () => {
                    i(), this._update(), this._updateWireInterest(e)
                }), e.on("have", () => {
                    i(), this._update(), this._updateWireInterest(e)
                }), e.once("interested", () => {
                    e.unchoke()
                }), e.once("close", () => {
                    clearTimeout(t)
                }), e.on("choke", () => {
                    clearTimeout(t), (t = setTimeout(r, 5e3)).unref && t.unref()
                }), e.on("unchoke", () => {
                    clearTimeout(t), this._update()
                }), e.on("request", (t, r, n, i) => {
                    if (n > 131072) return e.destroy();
                    this.pieces[t] || this.store.get(t, {offset: r, length: n}, i)
                }), e.bitfield(this.bitfield), this._updateWireInterest(e), e.peerExtensions.dht && this.client.dht && this.client.dht.listening && e.port(this.client.dht.address().port), "webSeed" !== e.type && (t = setTimeout(r, 5e3)).unref && t.unref(), e.isSeeder = !1, i()
            }

            _updateSelections() {
                this.ready && !this.destroyed && (e.nextTick(() => {
                    this._gcSelections()
                }), this._updateInterest(), this._update())
            }

            _gcSelections() {
                for (let e = 0; e < this._selections.length; ++e) {
                    const t = this._selections[e], r = t.offset;
                    for (; this.bitfield.get(t.from + t.offset) && t.from + t.offset < t.to;) t.offset += 1;
                    r !== t.offset && t.notify(), t.to === t.from + t.offset && this.bitfield.get(t.from + t.offset) && (this._selections.splice(e, 1), e -= 1, t.notify(), this._updateInterest())
                }
                this._selections.length || this.emit("idle")
            }

            _updateInterest() {
                const e = this._amInterested;
                this._amInterested = !!this._selections.length, this.wires.forEach(e => this._updateWireInterest(e)), e !== this._amInterested && (this._amInterested ? this.emit("interested") : this.emit("uninterested"))
            }

            _updateWireInterest(e) {
                let t = !1;
                for (let r = 0; r < this.pieces.length; ++r) if (this.pieces[r] && e.peerPieces.get(r)) {
                    t = !0;
                    break
                }
                t ? e.interested() : e.uninterested()
            }

            _update() {
                if (this.destroyed) return;
                const e = (t = this.wires, r = 0, function () {
                    if (r === t.length) return null;
                    var e = t.length - r, n = Math.random() * e | 0, i = t[r + n], s = t[r];
                    return t[r] = i, t[r + n] = s, r++, i
                });
                var t, r;
                let n;
                for (; n = e();) this._updateWireWrapper(n)
            }

            _updateWireWrapper(e) {
                const t = this;
                "undefined" != typeof window && "function" == typeof window.requestIdleCallback ? window.requestIdleCallback((function () {
                    t._updateWire(e)
                }), {timeout: 250}) : t._updateWire(e)
            }

            _updateWire(e) {
                const t = this;
                if (e.peerChoking) return;
                if (!e.downloaded) return function () {
                    if (e.requests.length) return;
                    let r = t._selections.length;
                    for (; r--;) {
                        const n = t._selections[r];
                        let s;
                        if ("rarest" === t.strategy) {
                            const r = n.from + n.offset, o = n.to, a = o - r + 1, h = {};
                            let u = 0;
                            const c = i(r, o, h);
                            for (; u < a && !((s = t._rarityMap.getRarestPiece(c)) < 0);) {
                                if (t._request(e, s, !1)) return;
                                h[s] = !0, u += 1
                            }
                        } else for (s = n.to; s >= n.from + n.offset; --s) if (e.peerPieces.get(s) && t._request(e, s, !1)) return
                    }
                }();
                const r = c(e, .5);
                if (e.requests.length >= r) return;
                const n = c(e, 1);

                function i(t, r, n, i) {
                    return s => s >= t && s <= r && !(s in n) && e.peerPieces.get(s) && (!i || i(s))
                }

                function o(e) {
                    let r = e;
                    for (let i = e; i < t._selections.length && t._selections[i].priority; i++) r = i;
                    const n = t._selections[e];
                    t._selections[e] = t._selections[r], t._selections[r] = n
                }

                function a(r) {
                    if (e.requests.length >= n) return !0;
                    const a = function () {
                        const r = e.downloadSpeed() || 1;
                        if (r > s) return () => !0;
                        const n = Math.max(1, e.requests.length) * Qi.BLOCK_LENGTH / r;
                        let i = 10, o = 0;
                        return e => {
                            if (!i || t.bitfield.get(e)) return !0;
                            let a = t.pieces[e].missing;
                            for (; o < t.wires.length; o++) {
                                const h = t.wires[o], u = h.downloadSpeed();
                                if (!(u < s) && !(u <= r) && h.peerPieces.get(e) && !((a -= u * n) > 0)) return i--, !1
                            }
                            return !0
                        }
                    }();
                    for (let s = 0; s < t._selections.length; s++) {
                        const h = t._selections[s];
                        let u;
                        if ("rarest" === t.strategy) {
                            const c = h.from + h.offset, d = h.to, l = d - c + 1, f = {};
                            let p = 0;
                            const m = i(c, d, f, a);
                            for (; p < l && !((u = t._rarityMap.getRarestPiece(m)) < 0);) {
                                for (; t._request(e, u, t._critical[u] || r);) ;
                                if (!(e.requests.length < n)) return h.priority && o(s), !0;
                                f[u] = !0, p++
                            }
                        } else for (u = h.from + h.offset; u <= h.to; u++) if (e.peerPieces.get(u) && a(u)) {
                            for (; t._request(e, u, t._critical[u] || r);) ;
                            if (!(e.requests.length < n)) return h.priority && o(s), !0
                        }
                    }
                    return !1
                }

                a(!1) || a(!0)
            }

            _rechoke() {
                if (!this.ready) return;
                const e = this.wires.map(e => ({wire: e, random: Math.random()})).sort((e, t) => {
                    const r = e.wire, n = t.wire;
                    return r.downloadSpeed() !== n.downloadSpeed() ? r.downloadSpeed() - n.downloadSpeed() : r.uploadSpeed() !== n.uploadSpeed() ? r.uploadSpeed() - n.uploadSpeed() : r.amChoking !== n.amChoking ? r.amChoking ? -1 : 1 : e.random - t.random
                }).map(e => e.wire);
                this._rechokeOptimisticTime <= 0 ? this._rechokeOptimisticWire = null : this._rechokeOptimisticTime -= 1;
                let t = 0;
                for (; e.length > 0 && t < this._rechokeNumSlots - 1;) {
                    const r = e.pop();
                    r.isSeeder || r === this._rechokeOptimisticWire || (r.unchoke(), r.peerInterested && t++)
                }
                if (null === this._rechokeOptimisticWire && this._rechokeNumSlots > 0) {
                    const t = e.filter(e => e.peerInterested);
                    if (t.length > 0) {
                        const e = t[(r = t.length, Math.random() * r | 0)];
                        e.unchoke(), this._rechokeOptimisticWire = e, this._rechokeOptimisticTime = 2
                    }
                }
                var r;
                e.filter(e => e !== this._rechokeOptimisticWire).forEach(e => e.choke())
            }

            _hotswap(e, t) {
                const r = e.downloadSpeed();
                if (r < Qi.BLOCK_LENGTH) return !1;
                if (!this._reservations[t]) return !1;
                const n = this._reservations[t];
                if (!n) return !1;
                let i, o, a = 1 / 0;
                for (o = 0; o < n.length; o++) {
                    const t = n[o];
                    if (!t || t === e) continue;
                    const h = t.downloadSpeed();
                    h >= s || 2 * h > r || h > a || (i = t, a = h)
                }
                if (!i) return !1;
                for (o = 0; o < n.length; o++) n[o] === i && (n[o] = null);
                for (o = 0; o < i.requests.length; o++) {
                    const e = i.requests[o];
                    e.piece === t && this.pieces[t].cancel(e.offset / Qi.BLOCK_LENGTH | 0)
                }
                return this.emit("hotswap", i, e, t), !0
            }

            _request(t, r, n) {
                const i = this, s = t.requests.length, o = "webSeed" === t.type;
                if (i.bitfield.get(r)) return !1;
                if (s >= (o ? Math.min(function (e, t, r) {
                    return 1 + Math.ceil(1 * e.downloadSpeed() / r)
                }(t, 0, i.pieceLength), i.maxWebConns) : c(t, 1))) return !1;
                const a = i.pieces[r];
                let h = o ? a.reserveRemaining() : a.reserve();
                if (-1 === h && n && i._hotswap(t, r) && (h = o ? a.reserveRemaining() : a.reserve()), -1 === h) return !1;
                let u = i._reservations[r];
                u || (u = i._reservations[r] = []);
                let d = u.indexOf(null);
                -1 === d && (d = u.length), u[d] = t;
                const l = a.chunkOffset(h), f = o ? a.chunkLengthRemaining(h) : a.chunkLength(h);

                function p() {
                    e.nextTick(() => {
                        i._update()
                    })
                }

                return t.request(r, l, f, (function e(n, s) {
                    if (i.destroyed) return;
                    if (!i.ready) return i.once("ready", () => {
                        e(n, s)
                    });
                    if (u[d] === t && (u[d] = null), a !== i.pieces[r]) return p();
                    if (n) return i._debug("error getting piece %s (offset: %s length: %s) from %s: %s", r, l, f, `${t.remoteAddress}:${t.remotePort}`, n.message), o ? a.cancelRemaining(h) : a.cancel(h), void p();
                    if (i._debug("got piece %s (offset: %s length: %s) from %s", r, l, f, `${t.remoteAddress}:${t.remotePort}`), !a.set(h, s, t)) return p();
                    const c = a.flush();
                    Wt(c, e => {
                        if (!i.destroyed) {
                            if (e === i._hashes[r]) {
                                if (!i.pieces[r]) return;
                                i._debug("piece verified %s", r), i.pieces[r] = null, i._reservations[r] = null, i.bitfield.set(r, !0), i.store.put(r, c), i.wires.forEach(e => {
                                    e.have(r)
                                }), i._checkDone() && !i.destroyed && i.discovery.complete()
                            } else i.pieces[r] = new Qi(a.length), i.emit("warning", new Error(`Piece ${r} failed verification`));
                            p()
                        }
                    })
                })), !0
            }

            _checkDone() {
                if (this.destroyed) return;
                this.files.forEach(e => {
                    if (!e.done) {
                        for (let t = e._startPiece; t <= e._endPiece; ++t) if (!this.bitfield.get(t)) return;
                        e.done = !0, e.emit("done"), this._debug("file done: " + e.name)
                    }
                });
                let e = !0;
                for (let t = 0; t < this._selections.length; t++) {
                    const r = this._selections[t];
                    for (let t = r.from; t <= r.to; t++) if (!this.bitfield.get(t)) {
                        e = !1;
                        break
                    }
                    if (!e) break
                }
                return !this.done && e && (this.done = !0, this._debug("torrent done: " + this.infoHash), this.emit("done")), this._gcSelections(), e
            }

            load(e, t) {
                if (this.destroyed) throw new Error("torrent is destroyed");
                if (!this.ready) return this.once("ready", () => {
                    this.load(e, t)
                });
                Array.isArray(e) || (e = [e]), t || (t = d);
                const r = new At(e), n = new Fi(this.store, this.pieceLength);
                ns(r, n, e => {
                    if (e) return t(e);
                    this._markAllVerified(), this._checkDone(), t(null)
                })
            }

            createServer(e) {
                if ("function" != typeof ae) throw new Error("node.js-only method");
                if (this.destroyed) throw new Error("torrent is destroyed");
                const t = new ae(this, e);
                return this._servers.push(t), t
            }

            pause() {
                this.destroyed || (this._debug("pause"), this.paused = !0)
            }

            resume() {
                this.destroyed || (this._debug("resume"), this.paused = !1, this._drain())
            }

            _debug() {
                const e = [].slice.call(arguments);
                e[0] = `[${this.client ? this.client._debugId : "No Client"}] [${this._debugId}] ${e[0]}`, n(...e)
            }

            _drain() {
                if (this._debug("_drain numConns %s maxConns %s", this._numConns, this.client.maxConns), "function" != typeof ae.connect || this.destroyed || this.paused || this._numConns >= this.client.maxConns) return;
                this._debug("drain (%s queued, %s/%s peers)", this._numQueued, this.numPeers, this.client.maxConns);
                const e = this._queue.shift();
                if (!e) return;
                this._debug("%s connect attempt to %s", e.type, e.addr);
                const t = Oi(e.addr), r = {host: t[0], port: t[1]};
                "utpOutgoing" === e.type ? e.conn = ae.connect(r.port, r.host) : e.conn = ae.connect(r);
                const n = e.conn;
                n.once("connect", () => {
                    e.onConnect()
                }), n.once("error", t => {
                    e.destroy(t)
                }), e.startConnectTimeout(), n.on("close", () => {
                    if (this.destroyed) return;
                    if (e.retries >= a.length) {
                        if (this.client.utp) {
                            const t = this._addPeer(e.addr, "tcp");
                            t && (t.retries = 0)
                        } else this._debug("conn %s closed: will not re-add (max %s attempts)", e.addr, a.length);
                        return
                    }
                    const t = a[e.retries];
                    this._debug("conn %s closed: will re-add to queue in %sms (attempt %s)", e.addr, t, e.retries + 1);
                    const r = setTimeout(() => {
                        if (this.destroyed) return;
                        const t = this._addPeer(e.addr, this.client.utp ? "utp" : "tcp");
                        t && (t.retries = e.retries + 1)
                    }, t);
                    r.unref && r.unref()
                })
            }

            _validAddr(e) {
                let t;
                try {
                    t = Oi(e)
                } catch (i) {
                    return !1
                }
                const r = t[0], n = t[1];
                return n > 0 && n < 65535 && !("127.0.0.1" === r && n === this.client.torrentPort)
            }
        }
    }).call(this)
}).call(this, _e, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {});
var to = {};
(function (e, t, r) {
    (function () {
        const {EventEmitter: n} = $, i = Xt("webtorrent"), s = Ks,
            o = s.replace(/\d*./g, e => ("0" + e % 100).slice(-2)).slice(0, 4), a = `-WW${o}-`;

        class h extends n {
            constructor(n = {}) {
                super(), "string" == typeof n.peerId ? this.peerId = n.peerId : r.isBuffer(n.peerId) ? this.peerId = n.peerId.toString("hex") : this.peerId = r.from(a + bi(9).toString("base64")).toString("hex"), this.peerIdBuffer = r.from(this.peerId, "hex"), "string" == typeof n.nodeId ? this.nodeId = n.nodeId : r.isBuffer(n.nodeId) ? this.nodeId = n.nodeId.toString("hex") : this.nodeId = bi(20).toString("hex"), this.nodeIdBuffer = r.from(this.nodeId, "hex"), this._debugId = this.peerId.toString("hex").substring(0, 7), this.destroyed = !1, this.listening = !1, this.torrentPort = n.torrentPort || 0, this.dhtPort = n.dhtPort || 0, this.tracker = void 0 !== n.tracker ? n.tracker : {}, this.lsd = !1 !== n.lsd, this.torrents = [], this.maxConns = Number(n.maxConns) || 55, this.utp = !0 === n.utp, this._debug("new webtorrent (peerId %s, nodeId %s, port %s)", this.peerId, this.nodeId, this.torrentPort), this.tracker && ("object" != typeof this.tracker && (this.tracker = {}), n.rtcConfig && (console.warn("WebTorrent: opts.rtcConfig is deprecated. Use opts.tracker.rtcConfig instead"), this.tracker.rtcConfig = n.rtcConfig), n.wrtc && (console.warn("WebTorrent: opts.wrtc is deprecated. Use opts.tracker.wrtc instead"), this.tracker.wrtc = n.wrtc), t.WRTC && !this.tracker.wrtc && (this.tracker.wrtc = t.WRTC)), "function" == typeof ae ? this._connPool = new ae(this) : e.nextTick(() => {
                    this._onListening()
                }), this._downloadSpeed = Li(), this._uploadSpeed = Li(), !1 !== n.dht && "function" == typeof ae ? (this.dht = new ae(Object.assign({}, {nodeId: this.nodeId}, n.dht)), this.dht.once("error", e => {
                    this._destroy(e)
                }), this.dht.once("listening", () => {
                    const e = this.dht.address();
                    e && (this.dhtPort = e.port)
                }), this.dht.setMaxListeners(0), this.dht.listen(this.dhtPort)) : this.dht = !1, this.enableWebSeeds = !1 !== n.webSeeds;
                const i = () => {
                    this.destroyed || (this.ready = !0, this.emit("ready"))
                };
                "function" == typeof ae && null != n.blocklist ? ae(n.blocklist, {headers: {"user-agent": `WebTorrent/${s} (https://webtorrent.io)`}}, (e, t) => {
                    if (e) return this.error("Failed to load blocklist: " + e.message);
                    this.blocked = t, i()
                }) : e.nextTick(i)
            }

            get downloadSpeed() {
                return this._downloadSpeed()
            }

            get uploadSpeed() {
                return this._uploadSpeed()
            }

            get progress() {
                const e = this.torrents.filter(e => 1 !== e.progress);
                return e.reduce((e, t) => e + t.downloaded, 0) / (e.reduce((e, t) => e + (t.length || 0), 0) || 1)
            }

            get ratio() {
                return this.torrents.reduce((e, t) => e + t.uploaded, 0) / (this.torrents.reduce((e, t) => e + t.received, 0) || 1)
            }

            get(e) {
                if (e instanceof eo) {
                    if (this.torrents.includes(e)) return e
                } else {
                    let t;
                    try {
                        t = _i(e)
                    } catch (Ga) {
                    }
                    if (!t) return null;
                    if (!t.infoHash) throw new Error("Invalid torrent identifier");
                    for (const e of this.torrents) if (e.infoHash === t.infoHash) return e
                }
                return null
            }

            download(e, t, r) {
                return console.warn("WebTorrent: client.download() is deprecated. Use client.add() instead"), this.add(e, t, r)
            }

            add(e, t = {}, r = (() => {
            })) {
                if (this.destroyed) throw new Error("client is destroyed");
                "function" == typeof t && ([t, r] = [{}, t]);
                const n = () => {
                    if (!this.destroyed) for (const e of this.torrents) if (e.infoHash === s.infoHash && e !== s) return void s._destroy(new Error("Cannot add duplicate torrent " + s.infoHash))
                }, i = () => {
                    this.destroyed || (r(s), this.emit("torrent", s))
                };
                this._debug("add"), t = t ? Object.assign({}, t) : {};
                const s = new eo(e, this, t);
                return this.torrents.push(s), s.once("_infoHash", n), s.once("ready", i), s.once("close", (function e() {
                    s.removeListener("_infoHash", n), s.removeListener("ready", i), s.removeListener("close", e)
                })), s
            }

            seed(e, t, r) {
                if (this.destroyed) throw new Error("client is destroyed");
                "function" == typeof t && ([t, r] = [{}, t]), this._debug("seed"), (t = t ? Object.assign({}, t) : {}).skipVerify = !0;
                const n = "string" == typeof e;
                n && (t.path = ft.dirname(e)), t.createdBy || (t.createdBy = "WebTorrent/" + o);
                const i = e => {
                    this._debug("on seed"), "function" == typeof r && r(e), e.emit("seed"), this.emit("seed", e)
                }, s = this.add(null, t, e => {
                    const t = [t => {
                        if (n) return t();
                        e.load(a, t)
                    }];
                    this.dht && t.push(t => {
                        e.once("dhtAnnounce", t)
                    }), Mt(t, t => {
                        if (!this.destroyed) return t ? e._destroy(t) : void i(e)
                    })
                });
                let a;
                return "undefined" != typeof FileList && e instanceof FileList ? e = Array.from(e) : Array.isArray(e) || (e = [e]), Mt(e.map(e => t => {
                    !function (e) {
                        return "object" == typeof e && null != e && "function" == typeof e.pipe
                    }(e) ? t(null, e) : er(e, t)
                }), (e, r) => {
                    if (!this.destroyed) return e ? s._destroy(e) : void Gt.parseInput(r, t, (e, n) => {
                        if (!this.destroyed) {
                            if (e) return s._destroy(e);
                            a = n.map(e => e.getStream), Gt(r, t, (e, t) => {
                                if (this.destroyed) return;
                                if (e) return s._destroy(e);
                                const r = this.get(t);
                                r ? s._destroy(new Error("Cannot add duplicate torrent " + r.infoHash)) : s._onTorrentId(t)
                            })
                        }
                    })
                }), s
            }

            remove(e, t, r) {
                if ("function" == typeof t) return this.remove(e, null, t);
                if (this._debug("remove"), !this.get(e)) throw new Error("No torrent with id " + e);
                this._remove(e, t, r)
            }

            _remove(e, t, r) {
                if ("function" == typeof t) return this._remove(e, null, t);
                const n = this.get(e);
                n && (this.torrents.splice(this.torrents.indexOf(n), 1), n.destroy(t, r))
            }

            address() {
                return this.listening ? this._connPool ? this._connPool.tcpServer.address() : {
                    address: "0.0.0.0",
                    family: "IPv4",
                    port: 0
                } : null
            }

            destroy(e) {
                if (this.destroyed) throw new Error("client already destroyed");
                this._destroy(null, e)
            }

            _destroy(e, t) {
                this._debug("client destroy"), this.destroyed = !0;
                const r = this.torrents.map(e => t => {
                    e.destroy(t)
                });
                this._connPool && r.push(e => {
                    this._connPool.destroy(e)
                }), this.dht && r.push(e => {
                    this.dht.destroy(e)
                }), Mt(r, t), e && this.emit("error", e), this.torrents = [], this._connPool = null, this.dht = null
            }

            _onListening() {
                if (this._debug("listening"), this.listening = !0, this._connPool) {
                    const e = this._connPool.tcpServer.address();
                    e && (this.torrentPort = e.port)
                }
                this.emit("listening")
            }

            _debug() {
                const e = [].slice.call(arguments);
                e[0] = `[${this._debugId}] ${e[0]}`, i(...e)
            }
        }

        h.WEBRTC_SUPPORT = Si.WEBRTC_SUPPORT, h.VERSION = s, to = h
    }).call(this)
}).call(this, _e, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {}, y({}).Buffer);
var ro = io, no = $.EventEmitter;

function io() {
    no.call(this)
}

De(io, no), io.Readable = d({}), io.Writable = _({}), io.Duplex = g({}), io.Transform = qe, io.PassThrough = rt, io.finished = p({}), io.pipeline = ct, io.Stream = io, io.prototype.pipe = function (e, t) {
    var r = this;

    function n(t) {
        e.writable && !1 === e.write(t) && r.pause && r.pause()
    }

    function i() {
        r.readable && r.resume && r.resume()
    }

    r.on("data", n), e.on("drain", i), e._isStdio || t && !1 === t.end || (r.on("end", o), r.on("close", a));
    var s = !1;

    function o() {
        s || (s = !0, e.end())
    }

    function a() {
        s || (s = !0, "function" == typeof e.destroy && e.destroy())
    }

    function h(e) {
        if (u(), 0 === no.listenerCount(this, "error")) throw e
    }

    function u() {
        r.removeListener("data", n), e.removeListener("drain", i), r.removeListener("end", o), r.removeListener("close", a), r.removeListener("error", h), e.removeListener("error", h), r.removeListener("end", u), r.removeListener("close", u), e.removeListener("close", u)
    }

    return r.on("error", h), e.on("error", h), r.on("end", u), r.on("close", u), e.on("close", u), e.emit("pipe", r), e
};
var so = ro, oo = {};
(function (e) {
    (function () {
        "use strict";
        oo = "function" == typeof e ? e : function () {
            var e = [].slice.apply(arguments);
            e.splice(1, 0, 0), setTimeout.apply(null, e)
        }
    }).call(this)
}).call(this, i({}).setImmediate);
var ao = {};
(function (e) {
    (function () {
        "use strict";
        var t, r, n = e.MutationObserver || e.WebKitMutationObserver;
        if (n) {
            var i = 0, s = new n(u), o = e.document.createTextNode("");
            s.observe(o, {characterData: !0}), t = function () {
                o.data = i = ++i % 2
            }
        } else if (e.setImmediate || void 0 === e.MessageChannel) t = "document" in e && "onreadystatechange" in e.document.createElement("script") ? function () {
            var t = e.document.createElement("script");
            t.onreadystatechange = function () {
                u(), t.onreadystatechange = null, t.parentNode.removeChild(t), t = null
            }, e.document.documentElement.appendChild(t)
        } : function () {
            setTimeout(u, 0)
        }; else {
            var a = new e.MessageChannel;
            a.port1.onmessage = u, t = function () {
                a.port2.postMessage(0)
            }
        }
        var h = [];

        function u() {
            var e, t;
            r = !0;
            for (var n = h.length; n;) {
                for (t = h, h = [], e = -1; ++e < n;) t[e]();
                n = h.length
            }
            r = !1
        }

        ao = function (e) {
            1 !== h.push(e) || r || t()
        }
    }).call(this)
}).call(this, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {});
var ho;

function uo() {
}

var co = {}, lo = ["REJECTED"], fo = ["FULFILLED"], po = ["PENDING"];

function mo(e) {
    if ("function" != typeof e) throw new TypeError("resolver must be a function");
    this.state = po, this.queue = [], this.outcome = void 0, e !== uo && bo(this, e)
}

function go(e, t, r) {
    this.promise = e, "function" == typeof t && (this.onFulfilled = t, this.callFulfilled = this.otherCallFulfilled), "function" == typeof r && (this.onRejected = r, this.callRejected = this.otherCallRejected)
}

function _o(e, t, r) {
    ao((function () {
        var n;
        try {
            n = t(r)
        } catch (i) {
            return co.reject(e, i)
        }
        n === e ? co.reject(e, new TypeError("Cannot resolve promise with itself")) : co.resolve(e, n)
    }))
}

function yo(e) {
    var t = e && e.then;
    if (e && ("object" == typeof e || "function" == typeof e) && "function" == typeof t) return function () {
        t.apply(e, arguments)
    }
}

function bo(e, t) {
    var r = !1;

    function n(t) {
        r || (r = !0, co.reject(e, t))
    }

    function i(t) {
        r || (r = !0, co.resolve(e, t))
    }

    var s = vo((function () {
        t(i, n)
    }));
    "error" === s.status && n(s.value)
}

function vo(e, t) {
    var r = {};
    try {
        r.value = e(t), r.status = "success"
    } catch (n) {
        r.status = "error", r.value = n
    }
    return r
}

ho = mo, mo.prototype.finally = function (e) {
    if ("function" != typeof e) return this;
    var t = this.constructor;
    return this.then((function (r) {
        return t.resolve(e()).then((function () {
            return r
        }))
    }), (function (r) {
        return t.resolve(e()).then((function () {
            throw r
        }))
    }))
}, mo.prototype.catch = function (e) {
    return this.then(null, e)
}, mo.prototype.then = function (e, t) {
    if ("function" != typeof e && this.state === fo || "function" != typeof t && this.state === lo) return this;
    var r = new this.constructor(uo);
    return this.state !== po ? _o(r, this.state === fo ? e : t, this.outcome) : this.queue.push(new go(r, e, t)), r
}, go.prototype.callFulfilled = function (e) {
    co.resolve(this.promise, e)
}, go.prototype.otherCallFulfilled = function (e) {
    _o(this.promise, this.onFulfilled, e)
}, go.prototype.callRejected = function (e) {
    co.reject(this.promise, e)
}, go.prototype.otherCallRejected = function (e) {
    _o(this.promise, this.onRejected, e)
}, co.resolve = function (e, t) {
    var r = vo(yo, t);
    if ("error" === r.status) return co.reject(e, r.value);
    var n = r.value;
    if (n) bo(e, n); else {
        e.state = fo, e.outcome = t;
        for (var i = -1, s = e.queue.length; ++i < s;) e.queue[i].callFulfilled(t)
    }
    return e
}, co.reject = function (e, t) {
    e.state = lo, e.outcome = t;
    for (var r = -1, n = e.queue.length; ++r < n;) e.queue[r].callRejected(t);
    return e
}, mo.resolve = function (e) {
    return e instanceof this ? e : co.resolve(new this(uo), e)
}, mo.reject = function (e) {
    var t = new this(uo);
    return co.reject(t, e)
}, mo.all = function (e) {
    var t = this;
    if ("[object Array]" !== Object.prototype.toString.call(e)) return this.reject(new TypeError("must be an array"));
    var r = e.length, n = !1;
    if (!r) return this.resolve([]);
    for (var i = new Array(r), s = 0, o = -1, a = new this(uo); ++o < r;) h(e[o], o);
    return a;

    function h(e, o) {
        t.resolve(e).then((function (e) {
            i[o] = e, ++s !== r || n || (n = !0, co.resolve(a, i))
        }), (function (e) {
            n || (n = !0, co.reject(a, e))
        }))
    }
}, mo.race = function (e) {
    if ("[object Array]" !== Object.prototype.toString.call(e)) return this.reject(new TypeError("must be an array"));
    var t = e.length, r = !1;
    if (!t) return this.resolve([]);
    for (var n, i = -1, s = new this(uo); ++i < t;) n = e[i], this.resolve(n).then((function (e) {
        r || (r = !0, co.resolve(s, e))
    }), (function (e) {
        r || (r = !0, co.reject(s, e))
    }));
    return s
};
var wo = {};
wo = {Promise: "undefined" != typeof Promise ? Promise : ho};
for (var Eo = {}, ko = n({}), So = a({}), Co = s({}), xo = r({}), Ao = new Array(256), To = 0; To < 256; To++) Ao[To] = To >= 252 ? 6 : To >= 248 ? 5 : To >= 240 ? 4 : To >= 224 ? 3 : To >= 192 ? 2 : 1;

function Io() {
    xo.call(this, "utf-8 decode"), this.leftOver = null
}

function Ro() {
    xo.call(this, "utf-8 encode")
}

Ao[254] = Ao[254] = 1, Eo.utf8encode = function (e) {
    return So.nodebuffer ? Co.newBufferFrom(e, "utf-8") : function (e) {
        var t, r, n, i, s, o = e.length, a = 0;
        for (i = 0; i < o; i++) 55296 == (64512 & (r = e.charCodeAt(i))) && i + 1 < o && 56320 == (64512 & (n = e.charCodeAt(i + 1))) && (r = 65536 + (r - 55296 << 10) + (n - 56320), i++), a += r < 128 ? 1 : r < 2048 ? 2 : r < 65536 ? 3 : 4;
        for (t = So.uint8array ? new Uint8Array(a) : new Array(a), s = 0, i = 0; s < a; i++) 55296 == (64512 & (r = e.charCodeAt(i))) && i + 1 < o && 56320 == (64512 & (n = e.charCodeAt(i + 1))) && (r = 65536 + (r - 55296 << 10) + (n - 56320), i++), r < 128 ? t[s++] = r : r < 2048 ? (t[s++] = 192 | r >>> 6, t[s++] = 128 | 63 & r) : r < 65536 ? (t[s++] = 224 | r >>> 12, t[s++] = 128 | r >>> 6 & 63, t[s++] = 128 | 63 & r) : (t[s++] = 240 | r >>> 18, t[s++] = 128 | r >>> 12 & 63, t[s++] = 128 | r >>> 6 & 63, t[s++] = 128 | 63 & r);
        return t
    }(e)
}, Eo.utf8decode = function (e) {
    return So.nodebuffer ? ko.transformTo("nodebuffer", e).toString("utf-8") : function (e) {
        var t, r, n, i, s = e.length, o = new Array(2 * s);
        for (r = 0, t = 0; t < s;) if ((n = e[t++]) < 128) o[r++] = n; else if ((i = Ao[n]) > 4) o[r++] = 65533, t += i - 1; else {
            for (n &= 2 === i ? 31 : 3 === i ? 15 : 7; i > 1 && t < s;) n = n << 6 | 63 & e[t++], i--;
            i > 1 ? o[r++] = 65533 : n < 65536 ? o[r++] = n : (n -= 65536, o[r++] = 55296 | n >> 10 & 1023, o[r++] = 56320 | 1023 & n)
        }
        return o.length !== r && (o.subarray ? o = o.subarray(0, r) : o.length = r), ko.applyFromCharCode(o)
    }(e = ko.transformTo(So.uint8array ? "uint8array" : "array", e))
}, ko.inherits(Io, xo), Io.prototype.processChunk = function (e) {
    var t = ko.transformTo(So.uint8array ? "uint8array" : "array", e.data);
    if (this.leftOver && this.leftOver.length) {
        if (So.uint8array) {
            var r = t;
            (t = new Uint8Array(r.length + this.leftOver.length)).set(this.leftOver, 0), t.set(r, this.leftOver.length)
        } else t = this.leftOver.concat(t);
        this.leftOver = null
    }
    var n = function (e, t) {
        var r;
        for ((t = t || e.length) > e.length && (t = e.length), r = t - 1; r >= 0 && 128 == (192 & e[r]);) r--;
        return r < 0 || 0 === r ? t : r + Ao[e[r]] > t ? r : t
    }(t), i = t;
    n !== t.length && (So.uint8array ? (i = t.subarray(0, n), this.leftOver = t.subarray(n, t.length)) : (i = t.slice(0, n), this.leftOver = t.slice(n, t.length))), this.push({
        data: Eo.utf8decode(i),
        meta: e.meta
    })
}, Io.prototype.flush = function () {
    this.leftOver && this.leftOver.length && (this.push({
        data: Eo.utf8decode(this.leftOver),
        meta: {}
    }), this.leftOver = null)
}, Eo.Utf8DecodeWorker = Io, ko.inherits(Ro, xo), Ro.prototype.processChunk = function (e) {
    this.push({data: Eo.utf8encode(e.data), meta: e.meta})
}, Eo.Utf8EncodeWorker = Ro;
var Bo = {}, Lo = r({}), Oo = n({});

function Uo(e) {
    Lo.call(this, "ConvertWorker to " + e), this.destType = e
}

Oo.inherits(Uo, Lo), Uo.prototype.processChunk = function (e) {
    this.push({data: Oo.transformTo(this.destType, e.data), meta: e.meta})
}, Bo = Uo;
var Po = {};
(function (e) {
    (function () {
        "use strict";
        var i = n({}), s = r({}), h = o({}), u = a({}), c = null;
        if (u.nodestream) try {
            c = t({})
        } catch (l) {
        }

        function d(e, t, r) {
            var n = t;
            switch (t) {
                case"blob":
                case"arraybuffer":
                    n = "uint8array";
                    break;
                case"base64":
                    n = "string"
            }
            try {
                this._internalType = n, this._outputType = t, this._mimeType = r, i.checkSupport(n), this._worker = e.pipe(new Bo(n)), e.lock()
            } catch (l) {
                this._worker = new s("error"), this._worker.error(l)
            }
        }

        d.prototype = {
            accumulate: function (t) {
                return r = this, n = t, new wo.Promise((function (t, s) {
                    var o = [], a = r._internalType, u = r._outputType, c = r._mimeType;
                    r.on("data", (function (e, t) {
                        o.push(e), n && n(t)
                    })).on("error", (function (e) {
                        o = [], s(e)
                    })).on("end", (function () {
                        try {
                            var r = function (e, t, r) {
                                switch (e) {
                                    case"blob":
                                        return i.newBlob(i.transformTo("arraybuffer", t), r);
                                    case"base64":
                                        return h.encode(t);
                                    default:
                                        return i.transformTo(e, t)
                                }
                            }(u, function (t, r) {
                                var n, i = 0, s = null, o = 0;
                                for (n = 0; n < r.length; n++) o += r[n].length;
                                switch (t) {
                                    case"string":
                                        return r.join("");
                                    case"array":
                                        return Array.prototype.concat.apply([], r);
                                    case"uint8array":
                                        for (s = new Uint8Array(o), n = 0; n < r.length; n++) s.set(r[n], i), i += r[n].length;
                                        return s;
                                    case"nodebuffer":
                                        return e.concat(r);
                                    default:
                                        throw new Error("concat : unsupported type '" + t + "'")
                                }
                            }(a, o), c);
                            t(r)
                        } catch (l) {
                            s(l)
                        }
                        o = []
                    })).resume()
                }));
                var r, n
            }, on: function (e, t) {
                var r = this;
                return "data" === e ? this._worker.on(e, (function (e) {
                    t.call(r, e.data, e.meta)
                })) : this._worker.on(e, (function () {
                    i.delay(t, arguments, r)
                })), this
            }, resume: function () {
                return i.delay(this._worker.resume, [], this._worker), this
            }, pause: function () {
                return this._worker.pause(), this
            }, toNodejsStream: function (e) {
                if (i.checkSupport("nodestream"), "nodebuffer" !== this._outputType) throw new Error(this._outputType + " is not supported by this method");
                return new c(this, {objectMode: "nodebuffer" !== this._outputType}, e)
            }
        }, Po = d
    }).call(this)
}).call(this, y({}).Buffer);
var Mo = {
    base64: !1,
    binary: !1,
    dir: !1,
    createFolders: !0,
    date: null,
    compression: null,
    compressionOptions: null,
    comment: null,
    unixPermissions: null,
    dosPermissions: null
}, Do = {}, No = n({}), jo = r({});

function Fo(e) {
    jo.call(this, "DataWorker");
    var t = this;
    this.dataIsReady = !1, this.index = 0, this.max = 0, this.data = null, this.type = "", this._tickScheduled = !1, e.then((function (e) {
        t.dataIsReady = !0, t.data = e, t.max = e && e.length || 0, t.type = No.getTypeOf(e), t.isPaused || t._tickAndRepeat()
    }), (function (e) {
        t.error(e)
    }))
}

No.inherits(Fo, jo), Fo.prototype.cleanUp = function () {
    jo.prototype.cleanUp.call(this), this.data = null
}, Fo.prototype.resume = function () {
    return !!jo.prototype.resume.call(this) && (!this._tickScheduled && this.dataIsReady && (this._tickScheduled = !0, No.delay(this._tickAndRepeat, [], this)), !0)
}, Fo.prototype._tickAndRepeat = function () {
    this._tickScheduled = !1, this.isPaused || this.isFinished || (this._tick(), this.isFinished || (No.delay(this._tickAndRepeat, [], this), this._tickScheduled = !0))
}, Fo.prototype._tick = function () {
    if (this.isPaused || this.isFinished) return !1;
    var e = null, t = Math.min(this.max, this.index + 16384);
    if (this.index >= this.max) return this.end();
    switch (this.type) {
        case"string":
            e = this.data.substring(this.index, t);
            break;
        case"uint8array":
            e = this.data.subarray(this.index, t);
            break;
        case"array":
        case"nodebuffer":
            e = this.data.slice(this.index, t)
    }
    return this.index = t, this.push({data: e, meta: {percent: this.max ? this.index / this.max * 100 : 0}})
}, Do = Fo;
var zo = n({}), Ho = function () {
    for (var e, t = [], r = 0; r < 256; r++) {
        e = r;
        for (var n = 0; n < 8; n++) e = 1 & e ? 3988292384 ^ e >>> 1 : e >>> 1;
        t[r] = e
    }
    return t
}(), Wo = function (e, t) {
    return void 0 !== e && e.length ? "string" !== zo.getTypeOf(e) ? function (e, t, r, n) {
        var i = Ho, s = 0 + r;
        e ^= -1;
        for (var o = 0; o < s; o++) e = e >>> 8 ^ i[255 & (e ^ t[o])];
        return -1 ^ e
    }(0 | t, e, e.length) : function (e, t, r, n) {
        var i = Ho, s = 0 + r;
        e ^= -1;
        for (var o = 0; o < s; o++) e = e >>> 8 ^ i[255 & (e ^ t.charCodeAt(o))];
        return -1 ^ e
    }(0 | t, e, e.length) : 0
}, qo = {}, Zo = r({});

function Vo() {
    Zo.call(this, "Crc32Probe"), this.withStreamInfo("crc32", 0)
}

n({}).inherits(Vo, Zo), Vo.prototype.processChunk = function (e) {
    this.streamInfo.crc32 = Wo(e.data, this.streamInfo.crc32 || 0), this.push(e)
}, qo = Vo;
var $o = {}, Ko = n({}), Go = r({});

function Xo(e) {
    Go.call(this, "DataLengthProbe for " + e), this.propName = e, this.withStreamInfo(e, 0)
}

Ko.inherits(Xo, Go), Xo.prototype.processChunk = function (e) {
    if (e) {
        var t = this.streamInfo[this.propName] || 0;
        this.streamInfo[this.propName] = t + e.data.length
    }
    Go.prototype.processChunk.call(this, e)
};
var Yo = {};

function Jo(e, t, r, n, i) {
    this.compressedSize = e, this.uncompressedSize = t, this.crc32 = r, this.compression = n, this.compressedContent = i
}

$o = $o = Xo, Jo.prototype = {
    getContentWorker: function () {
        var e = new Do(wo.Promise.resolve(this.compressedContent)).pipe(this.compression.uncompressWorker()).pipe(new $o("data_length")),
            t = this;
        return e.on("end", (function () {
            if (this.streamInfo.data_length !== t.uncompressedSize) throw new Error("Bug : uncompressed data size mismatch")
        })), e
    }, getCompressedWorker: function () {
        return new Do(wo.Promise.resolve(this.compressedContent)).withStreamInfo("compressedSize", this.compressedSize).withStreamInfo("uncompressedSize", this.uncompressedSize).withStreamInfo("crc32", this.crc32).withStreamInfo("compression", this.compression)
    }
}, Jo.createWorkerFrom = function (e, t, r) {
    return e.pipe(new qo).pipe(new $o("uncompressedSize")).pipe(t.compressWorker(r)).pipe(new $o("compressedSize")).withStreamInfo("compression", t)
}, Yo = Jo;
var Qo = r({}), ea = function (e, t, r) {
    this.name = e, this.dir = r.dir, this.date = r.date, this.comment = r.comment, this.unixPermissions = r.unixPermissions, this.dosPermissions = r.dosPermissions, this._data = t, this._dataBinary = r.binary, this.options = {
        compression: r.compression,
        compressionOptions: r.compressionOptions
    }
};
ea.prototype = {
    internalStream: function (e) {
        var t = null, r = "string";
        try {
            if (!e) throw new Error("No output type specified.");
            var n = "string" === (r = e.toLowerCase()) || "text" === r;
            "binarystring" !== r && "text" !== r || (r = "string"), t = this._decompressWorker();
            var i = !this._dataBinary;
            i && !n && (t = t.pipe(new Eo.Utf8EncodeWorker)), !i && n && (t = t.pipe(new Eo.Utf8DecodeWorker))
        } catch (s) {
            (t = new Qo("error")).error(s)
        }
        return new Po(t, r, "")
    }, async: function (e, t) {
        return this.internalStream(e).accumulate(t)
    }, nodeStream: function (e, t) {
        return this.internalStream(e || "nodebuffer").toNodejsStream(t)
    }, _compressWorker: function (e, t) {
        if (this._data instanceof Yo && this._data.compression.magic === e.magic) return this._data.getCompressedWorker();
        var r = this._decompressWorker();
        return this._dataBinary || (r = r.pipe(new Eo.Utf8EncodeWorker)), Yo.createWorkerFrom(r, e, t)
    }, _decompressWorker: function () {
        return this._data instanceof Yo ? this._data.getContentWorker() : this._data instanceof Qo ? this._data : new Do(this._data)
    }
};
for (var ta = ["asText", "asBinary", "asNodeBuffer", "asUint8Array", "asArrayBuffer"], ra = function () {
    throw new Error("This method has been removed in JSZip 3.0, please check the upgrade guide.")
}, na = 0; na < ta.length; na++) ea.prototype[ta[na]] = ra;
var ia = ea, sa = {},
    oa = "undefined" != typeof Uint8Array && "undefined" != typeof Uint16Array && "undefined" != typeof Int32Array;

function aa(e, t) {
    return Object.prototype.hasOwnProperty.call(e, t)
}

sa.assign = function (e) {
    for (var t = Array.prototype.slice.call(arguments, 1); t.length;) {
        var r = t.shift();
        if (r) {
            if ("object" != typeof r) throw new TypeError(r + "must be non-object");
            for (var n in r) aa(r, n) && (e[n] = r[n])
        }
    }
    return e
}, sa.shrinkBuf = function (e, t) {
    return e.length === t ? e : e.subarray ? e.subarray(0, t) : (e.length = t, e)
};
var ha = {
    arraySet: function (e, t, r, n, i) {
        if (t.subarray && e.subarray) e.set(t.subarray(r, r + n), i); else for (var s = 0; s < n; s++) e[i + s] = t[r + s]
    }, flattenChunks: function (e) {
        var t, r, n, i, s, o;
        for (n = 0, t = 0, r = e.length; t < r; t++) n += e[t].length;
        for (o = new Uint8Array(n), i = 0, t = 0, r = e.length; t < r; t++) s = e[t], o.set(s, i), i += s.length;
        return o
    }
}, ua = {
    arraySet: function (e, t, r, n, i) {
        for (var s = 0; s < n; s++) e[i + s] = t[r + s]
    }, flattenChunks: function (e) {
        return [].concat.apply([], e)
    }
};
sa.setTyped = function (e) {
    e ? (sa.Buf8 = Uint8Array, sa.Buf16 = Uint16Array, sa.Buf32 = Int32Array, sa.assign(sa, ha)) : (sa.Buf8 = Array, sa.Buf16 = Array, sa.Buf32 = Array, sa.assign(sa, ua))
}, sa.setTyped(oa);
var ca = {};

function da(e) {
    for (var t = e.length; --t >= 0;) e[t] = 0
}

var la = [0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 0],
    fa = [0, 0, 0, 0, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8, 8, 9, 9, 10, 10, 11, 11, 12, 12, 13, 13],
    pa = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 3, 7],
    ma = [16, 17, 18, 0, 8, 7, 9, 6, 10, 5, 11, 4, 12, 3, 13, 2, 14, 1, 15], ga = new Array(576);
da(ga);
var _a = new Array(60);
da(_a);
var ya = new Array(512);
da(ya);
var ba = new Array(256);
da(ba);
var va = new Array(29);
da(va);
var wa, Ea, ka, Sa = new Array(30);

function Ca(e, t, r, n, i) {
    this.static_tree = e, this.extra_bits = t, this.extra_base = r, this.elems = n, this.max_length = i, this.has_stree = e && e.length
}

function xa(e, t) {
    this.dyn_tree = e, this.max_code = 0, this.stat_desc = t
}

function Aa(e) {
    return e < 256 ? ya[e] : ya[256 + (e >>> 7)]
}

function Ta(e, t) {
    e.pending_buf[e.pending++] = 255 & t, e.pending_buf[e.pending++] = t >>> 8 & 255
}

function Ia(e, t, r) {
    e.bi_valid > 16 - r ? (e.bi_buf |= t << e.bi_valid & 65535, Ta(e, e.bi_buf), e.bi_buf = t >> 16 - e.bi_valid, e.bi_valid += r - 16) : (e.bi_buf |= t << e.bi_valid & 65535, e.bi_valid += r)
}

function Ra(e, t, r) {
    Ia(e, r[2 * t], r[2 * t + 1])
}

function Ba(e, t) {
    var r = 0;
    do {
        r |= 1 & e, e >>>= 1, r <<= 1
    } while (--t > 0);
    return r >>> 1
}

function La(e, t, r) {
    var n, i, s = new Array(16), o = 0;
    for (n = 1; n <= 15; n++) s[n] = o = o + r[n - 1] << 1;
    for (i = 0; i <= t; i++) {
        var a = e[2 * i + 1];
        0 !== a && (e[2 * i] = Ba(s[a]++, a))
    }
}

function Oa(e) {
    var t;
    for (t = 0; t < 286; t++) e.dyn_ltree[2 * t] = 0;
    for (t = 0; t < 30; t++) e.dyn_dtree[2 * t] = 0;
    for (t = 0; t < 19; t++) e.bl_tree[2 * t] = 0;
    e.dyn_ltree[512] = 1, e.opt_len = e.static_len = 0, e.last_lit = e.matches = 0
}

function Ua(e) {
    e.bi_valid > 8 ? Ta(e, e.bi_buf) : e.bi_valid > 0 && (e.pending_buf[e.pending++] = e.bi_buf), e.bi_buf = 0, e.bi_valid = 0
}

function Pa(e, t, r, n) {
    var i = 2 * t, s = 2 * r;
    return e[i] < e[s] || e[i] === e[s] && n[t] <= n[r]
}

function Ma(e, t, r) {
    for (var n = e.heap[r], i = r << 1; i <= e.heap_len && (i < e.heap_len && Pa(t, e.heap[i + 1], e.heap[i], e.depth) && i++, !Pa(t, n, e.heap[i], e.depth));) e.heap[r] = e.heap[i], r = i, i <<= 1;
    e.heap[r] = n
}

function Da(e, t, r) {
    var n, i, s, o, a = 0;
    if (0 !== e.last_lit) do {
        n = e.pending_buf[e.d_buf + 2 * a] << 8 | e.pending_buf[e.d_buf + 2 * a + 1], i = e.pending_buf[e.l_buf + a], a++, 0 === n ? Ra(e, i, t) : (Ra(e, (s = ba[i]) + 256 + 1, t), 0 !== (o = la[s]) && Ia(e, i -= va[s], o), Ra(e, s = Aa(--n), r), 0 !== (o = fa[s]) && Ia(e, n -= Sa[s], o))
    } while (a < e.last_lit);
    Ra(e, 256, t)
}

function Na(e, t) {
    var r, n, i, s = t.dyn_tree, o = t.stat_desc.static_tree, a = t.stat_desc.has_stree, h = t.stat_desc.elems, u = -1;
    for (e.heap_len = 0, e.heap_max = 573, r = 0; r < h; r++) 0 !== s[2 * r] ? (e.heap[++e.heap_len] = u = r, e.depth[r] = 0) : s[2 * r + 1] = 0;
    for (; e.heap_len < 2;) s[2 * (i = e.heap[++e.heap_len] = u < 2 ? ++u : 0)] = 1, e.depth[i] = 0, e.opt_len--, a && (e.static_len -= o[2 * i + 1]);
    for (t.max_code = u, r = e.heap_len >> 1; r >= 1; r--) Ma(e, s, r);
    i = h;
    do {
        r = e.heap[1], e.heap[1] = e.heap[e.heap_len--], Ma(e, s, 1), n = e.heap[1], e.heap[--e.heap_max] = r, e.heap[--e.heap_max] = n, s[2 * i] = s[2 * r] + s[2 * n], e.depth[i] = (e.depth[r] >= e.depth[n] ? e.depth[r] : e.depth[n]) + 1, s[2 * r + 1] = s[2 * n + 1] = i, e.heap[1] = i++, Ma(e, s, 1)
    } while (e.heap_len >= 2);
    e.heap[--e.heap_max] = e.heap[1], function (e, t) {
        var r, n, i, s, o, a, h = t.dyn_tree, u = t.max_code, c = t.stat_desc.static_tree, d = t.stat_desc.has_stree,
            l = t.stat_desc.extra_bits, f = t.stat_desc.extra_base, p = t.stat_desc.max_length, m = 0;
        for (s = 0; s <= 15; s++) e.bl_count[s] = 0;
        for (h[2 * e.heap[e.heap_max] + 1] = 0, r = e.heap_max + 1; r < 573; r++) (s = h[2 * h[2 * (n = e.heap[r]) + 1] + 1] + 1) > p && (s = p, m++), h[2 * n + 1] = s, n > u || (e.bl_count[s]++, o = 0, n >= f && (o = l[n - f]), a = h[2 * n], e.opt_len += a * (s + o), d && (e.static_len += a * (c[2 * n + 1] + o)));
        if (0 !== m) {
            do {
                for (s = p - 1; 0 === e.bl_count[s];) s--;
                e.bl_count[s]--, e.bl_count[s + 1] += 2, e.bl_count[p]--, m -= 2
            } while (m > 0);
            for (s = p; 0 !== s; s--) for (n = e.bl_count[s]; 0 !== n;) (i = e.heap[--r]) > u || (h[2 * i + 1] !== s && (e.opt_len += (s - h[2 * i + 1]) * h[2 * i], h[2 * i + 1] = s), n--)
        }
    }(e, t), La(s, u, e.bl_count)
}

function ja(e, t, r) {
    var n, i, s = -1, o = t[1], a = 0, h = 7, u = 4;
    for (0 === o && (h = 138, u = 3), t[2 * (r + 1) + 1] = 65535, n = 0; n <= r; n++) i = o, o = t[2 * (n + 1) + 1], ++a < h && i === o || (a < u ? e.bl_tree[2 * i] += a : 0 !== i ? (i !== s && e.bl_tree[2 * i]++, e.bl_tree[32]++) : a <= 10 ? e.bl_tree[34]++ : e.bl_tree[36]++, a = 0, s = i, 0 === o ? (h = 138, u = 3) : i === o ? (h = 6, u = 3) : (h = 7, u = 4))
}

function Fa(e, t, r) {
    var n, i, s = -1, o = t[1], a = 0, h = 7, u = 4;
    for (0 === o && (h = 138, u = 3), n = 0; n <= r; n++) if (i = o, o = t[2 * (n + 1) + 1], !(++a < h && i === o)) {
        if (a < u) do {
            Ra(e, i, e.bl_tree)
        } while (0 != --a); else 0 !== i ? (i !== s && (Ra(e, i, e.bl_tree), a--), Ra(e, 16, e.bl_tree), Ia(e, a - 3, 2)) : a <= 10 ? (Ra(e, 17, e.bl_tree), Ia(e, a - 3, 3)) : (Ra(e, 18, e.bl_tree), Ia(e, a - 11, 7));
        a = 0, s = i, 0 === o ? (h = 138, u = 3) : i === o ? (h = 6, u = 3) : (h = 7, u = 4)
    }
}

da(Sa);
var za = !1;

function Ha(e, t, r, n) {
    Ia(e, 0 + (n ? 1 : 0), 3), function (e, t, r, n) {
        Ua(e), Ta(e, r), Ta(e, ~r), sa.arraySet(e.pending_buf, e.window, t, r, e.pending), e.pending += r
    }(e, t, r)
}

ca._tr_init = function (e) {
    za || (function () {
        var e, t, r, n, i, s = new Array(16);
        for (r = 0, n = 0; n < 28; n++) for (va[n] = r, e = 0; e < 1 << la[n]; e++) ba[r++] = n;
        for (ba[r - 1] = n, i = 0, n = 0; n < 16; n++) for (Sa[n] = i, e = 0; e < 1 << fa[n]; e++) ya[i++] = n;
        for (i >>= 7; n < 30; n++) for (Sa[n] = i << 7, e = 0; e < 1 << fa[n] - 7; e++) ya[256 + i++] = n;
        for (t = 0; t <= 15; t++) s[t] = 0;
        for (e = 0; e <= 143;) ga[2 * e + 1] = 8, e++, s[8]++;
        for (; e <= 255;) ga[2 * e + 1] = 9, e++, s[9]++;
        for (; e <= 279;) ga[2 * e + 1] = 7, e++, s[7]++;
        for (; e <= 287;) ga[2 * e + 1] = 8, e++, s[8]++;
        for (La(ga, 287, s), e = 0; e < 30; e++) _a[2 * e + 1] = 5, _a[2 * e] = Ba(e, 5);
        wa = new Ca(ga, la, 257, 286, 15), Ea = new Ca(_a, fa, 0, 30, 15), ka = new Ca(new Array(0), pa, 0, 19, 7)
    }(), za = !0), e.l_desc = new xa(e.dyn_ltree, wa), e.d_desc = new xa(e.dyn_dtree, Ea), e.bl_desc = new xa(e.bl_tree, ka), e.bi_buf = 0, e.bi_valid = 0, Oa(e)
}, ca._tr_stored_block = Ha, ca._tr_flush_block = function (e, t, r, n) {
    var i, s, o = 0;
    e.level > 0 ? (2 === e.strm.data_type && (e.strm.data_type = function (e) {
        var t, r = 4093624447;
        for (t = 0; t <= 31; t++, r >>>= 1) if (1 & r && 0 !== e.dyn_ltree[2 * t]) return 0;
        if (0 !== e.dyn_ltree[18] || 0 !== e.dyn_ltree[20] || 0 !== e.dyn_ltree[26]) return 1;
        for (t = 32; t < 256; t++) if (0 !== e.dyn_ltree[2 * t]) return 1;
        return 0
    }(e)), Na(e, e.l_desc), Na(e, e.d_desc), o = function (e) {
        var t;
        for (ja(e, e.dyn_ltree, e.l_desc.max_code), ja(e, e.dyn_dtree, e.d_desc.max_code), Na(e, e.bl_desc), t = 18; t >= 3 && 0 === e.bl_tree[2 * ma[t] + 1]; t--) ;
        return e.opt_len += 3 * (t + 1) + 5 + 5 + 4, t
    }(e), i = e.opt_len + 3 + 7 >>> 3, (s = e.static_len + 3 + 7 >>> 3) <= i && (i = s)) : i = s = r + 5, r + 4 <= i && -1 !== t ? Ha(e, t, r, n) : 4 === e.strategy || s === i ? (Ia(e, 2 + (n ? 1 : 0), 3), Da(e, ga, _a)) : (Ia(e, 4 + (n ? 1 : 0), 3), function (e, t, r, n) {
        var i;
        for (Ia(e, t - 257, 5), Ia(e, r - 1, 5), Ia(e, n - 4, 4), i = 0; i < n; i++) Ia(e, e.bl_tree[2 * ma[i] + 1], 3);
        Fa(e, e.dyn_ltree, t - 1), Fa(e, e.dyn_dtree, r - 1)
    }(e, e.l_desc.max_code + 1, e.d_desc.max_code + 1, o + 1), Da(e, e.dyn_ltree, e.dyn_dtree)), Oa(e), n && Ua(e)
}, ca._tr_tally = function (e, t, r) {
    return e.pending_buf[e.d_buf + 2 * e.last_lit] = t >>> 8 & 255, e.pending_buf[e.d_buf + 2 * e.last_lit + 1] = 255 & t, e.pending_buf[e.l_buf + e.last_lit] = 255 & r, e.last_lit++, 0 === t ? e.dyn_ltree[2 * r]++ : (e.matches++, t--, e.dyn_ltree[2 * (ba[r] + 256 + 1)]++, e.dyn_dtree[2 * Aa(t)]++), e.last_lit === e.lit_bufsize - 1
}, ca._tr_align = function (e) {
    Ia(e, 2, 3), Ra(e, 256, ga), function (e) {
        16 === e.bi_valid ? (Ta(e, e.bi_buf), e.bi_buf = 0, e.bi_valid = 0) : e.bi_valid >= 8 && (e.pending_buf[e.pending++] = 255 & e.bi_buf, e.bi_buf >>= 8, e.bi_valid -= 8)
    }(e)
};
var Wa, qa = function (e, t, r, n) {
    for (var i = 65535 & e | 0, s = e >>> 16 & 65535 | 0, o = 0; 0 !== r;) {
        r -= o = r > 2e3 ? 2e3 : r;
        do {
            s = s + (i = i + t[n++] | 0) | 0
        } while (--o);
        i %= 65521, s %= 65521
    }
    return i | s << 16 | 0
}, Za = function () {
    for (var e, t = [], r = 0; r < 256; r++) {
        e = r;
        for (var n = 0; n < 8; n++) e = 1 & e ? 3988292384 ^ e >>> 1 : e >>> 1;
        t[r] = e
    }
    return t
}(), Va = function (e, t, r, n) {
    var i = Za, s = n + r;
    e ^= -1;
    for (var o = n; o < s; o++) e = e >>> 8 ^ i[255 & (e ^ t[o])];
    return -1 ^ e
}, $a = {
    2: "need dictionary",
    1: "stream end",
    0: "",
    "-1": "file error",
    "-2": "stream error",
    "-3": "data error",
    "-4": "insufficient memory",
    "-5": "buffer error",
    "-6": "incompatible version"
}, Ka = {};

function Ga(e, t) {
    return e.msg = $a[t], t
}

function Xa(e) {
    return (e << 1) - (e > 4 ? 9 : 0)
}

function Ya(e) {
    for (var t = e.length; --t >= 0;) e[t] = 0
}

function Ja(e) {
    var t = e.state, r = t.pending;
    r > e.avail_out && (r = e.avail_out), 0 !== r && (sa.arraySet(e.output, t.pending_buf, t.pending_out, r, e.next_out), e.next_out += r, t.pending_out += r, e.total_out += r, e.avail_out -= r, t.pending -= r, 0 === t.pending && (t.pending_out = 0))
}

function Qa(e, t) {
    ca._tr_flush_block(e, e.block_start >= 0 ? e.block_start : -1, e.strstart - e.block_start, t), e.block_start = e.strstart, Ja(e.strm)
}

function eh(e, t) {
    e.pending_buf[e.pending++] = t
}

function th(e, t) {
    e.pending_buf[e.pending++] = t >>> 8 & 255, e.pending_buf[e.pending++] = 255 & t
}

function rh(e, t) {
    var r, n, i = e.max_chain_length, s = e.strstart, o = e.prev_length, a = e.nice_match,
        h = e.strstart > e.w_size - 262 ? e.strstart - (e.w_size - 262) : 0, u = e.window, c = e.w_mask, d = e.prev,
        l = e.strstart + 258, f = u[s + o - 1], p = u[s + o];
    e.prev_length >= e.good_match && (i >>= 2), a > e.lookahead && (a = e.lookahead);
    do {
        if (u[(r = t) + o] === p && u[r + o - 1] === f && u[r] === u[s] && u[++r] === u[s + 1]) {
            s += 2, r++;
            do {
            } while (u[++s] === u[++r] && u[++s] === u[++r] && u[++s] === u[++r] && u[++s] === u[++r] && u[++s] === u[++r] && u[++s] === u[++r] && u[++s] === u[++r] && u[++s] === u[++r] && s < l);
            if (n = 258 - (l - s), s = l - 258, n > o) {
                if (e.match_start = t, o = n, n >= a) break;
                f = u[s + o - 1], p = u[s + o]
            }
        }
    } while ((t = d[t & c]) > h && 0 != --i);
    return o <= e.lookahead ? o : e.lookahead
}

function nh(e) {
    var t, r, n, i, s, o, a, h, u, c, d = e.w_size;
    do {
        if (i = e.window_size - e.lookahead - e.strstart, e.strstart >= d + (d - 262)) {
            sa.arraySet(e.window, e.window, d, d, 0), e.match_start -= d, e.strstart -= d, e.block_start -= d, t = r = e.hash_size;
            do {
                n = e.head[--t], e.head[t] = n >= d ? n - d : 0
            } while (--r);
            t = r = d;
            do {
                n = e.prev[--t], e.prev[t] = n >= d ? n - d : 0
            } while (--r);
            i += d
        }
        if (0 === e.strm.avail_in) break;
        if (o = e.strm, a = e.window, h = e.strstart + e.lookahead, u = i, c = void 0, (c = o.avail_in) > u && (c = u), r = 0 === c ? 0 : (o.avail_in -= c, sa.arraySet(a, o.input, o.next_in, c, h), 1 === o.state.wrap ? o.adler = qa(o.adler, a, c, h) : 2 === o.state.wrap && (o.adler = Va(o.adler, a, c, h)), o.next_in += c, o.total_in += c, c), e.lookahead += r, e.lookahead + e.insert >= 3) for (s = e.strstart - e.insert, e.ins_h = e.window[s], e.ins_h = (e.ins_h << e.hash_shift ^ e.window[s + 1]) & e.hash_mask; e.insert && (e.ins_h = (e.ins_h << e.hash_shift ^ e.window[s + 3 - 1]) & e.hash_mask, e.prev[s & e.w_mask] = e.head[e.ins_h], e.head[e.ins_h] = s, s++, e.insert--, !(e.lookahead + e.insert < 3));) ;
    } while (e.lookahead < 262 && 0 !== e.strm.avail_in)
}

function ih(e, t) {
    for (var r, n; ;) {
        if (e.lookahead < 262) {
            if (nh(e), e.lookahead < 262 && 0 === t) return 1;
            if (0 === e.lookahead) break
        }
        if (r = 0, e.lookahead >= 3 && (e.ins_h = (e.ins_h << e.hash_shift ^ e.window[e.strstart + 3 - 1]) & e.hash_mask, r = e.prev[e.strstart & e.w_mask] = e.head[e.ins_h], e.head[e.ins_h] = e.strstart), 0 !== r && e.strstart - r <= e.w_size - 262 && (e.match_length = rh(e, r)), e.match_length >= 3) if (n = ca._tr_tally(e, e.strstart - e.match_start, e.match_length - 3), e.lookahead -= e.match_length, e.match_length <= e.max_lazy_match && e.lookahead >= 3) {
            e.match_length--;
            do {
                e.strstart++, e.ins_h = (e.ins_h << e.hash_shift ^ e.window[e.strstart + 3 - 1]) & e.hash_mask, r = e.prev[e.strstart & e.w_mask] = e.head[e.ins_h], e.head[e.ins_h] = e.strstart
            } while (0 != --e.match_length);
            e.strstart++
        } else e.strstart += e.match_length, e.match_length = 0, e.ins_h = e.window[e.strstart], e.ins_h = (e.ins_h << e.hash_shift ^ e.window[e.strstart + 1]) & e.hash_mask; else n = ca._tr_tally(e, 0, e.window[e.strstart]), e.lookahead--, e.strstart++;
        if (n && (Qa(e, !1), 0 === e.strm.avail_out)) return 1
    }
    return e.insert = e.strstart < 2 ? e.strstart : 2, 4 === t ? (Qa(e, !0), 0 === e.strm.avail_out ? 3 : 4) : e.last_lit && (Qa(e, !1), 0 === e.strm.avail_out) ? 1 : 2
}

function sh(e, t) {
    for (var r, n, i; ;) {
        if (e.lookahead < 262) {
            if (nh(e), e.lookahead < 262 && 0 === t) return 1;
            if (0 === e.lookahead) break
        }
        if (r = 0, e.lookahead >= 3 && (e.ins_h = (e.ins_h << e.hash_shift ^ e.window[e.strstart + 3 - 1]) & e.hash_mask, r = e.prev[e.strstart & e.w_mask] = e.head[e.ins_h], e.head[e.ins_h] = e.strstart), e.prev_length = e.match_length, e.prev_match = e.match_start, e.match_length = 2, 0 !== r && e.prev_length < e.max_lazy_match && e.strstart - r <= e.w_size - 262 && (e.match_length = rh(e, r), e.match_length <= 5 && (1 === e.strategy || 3 === e.match_length && e.strstart - e.match_start > 4096) && (e.match_length = 2)), e.prev_length >= 3 && e.match_length <= e.prev_length) {
            i = e.strstart + e.lookahead - 3, n = ca._tr_tally(e, e.strstart - 1 - e.prev_match, e.prev_length - 3), e.lookahead -= e.prev_length - 1, e.prev_length -= 2;
            do {
                ++e.strstart <= i && (e.ins_h = (e.ins_h << e.hash_shift ^ e.window[e.strstart + 3 - 1]) & e.hash_mask, r = e.prev[e.strstart & e.w_mask] = e.head[e.ins_h], e.head[e.ins_h] = e.strstart)
            } while (0 != --e.prev_length);
            if (e.match_available = 0, e.match_length = 2, e.strstart++, n && (Qa(e, !1), 0 === e.strm.avail_out)) return 1
        } else if (e.match_available) {
            if ((n = ca._tr_tally(e, 0, e.window[e.strstart - 1])) && Qa(e, !1), e.strstart++, e.lookahead--, 0 === e.strm.avail_out) return 1
        } else e.match_available = 1, e.strstart++, e.lookahead--
    }
    return e.match_available && (n = ca._tr_tally(e, 0, e.window[e.strstart - 1]), e.match_available = 0), e.insert = e.strstart < 2 ? e.strstart : 2, 4 === t ? (Qa(e, !0), 0 === e.strm.avail_out ? 3 : 4) : e.last_lit && (Qa(e, !1), 0 === e.strm.avail_out) ? 1 : 2
}

function oh(e, t, r, n, i) {
    this.good_length = e, this.max_lazy = t, this.nice_length = r, this.max_chain = n, this.func = i
}

function ah() {
    this.strm = null, this.status = 0, this.pending_buf = null, this.pending_buf_size = 0, this.pending_out = 0, this.pending = 0, this.wrap = 0, this.gzhead = null, this.gzindex = 0, this.method = 8, this.last_flush = -1, this.w_size = 0, this.w_bits = 0, this.w_mask = 0, this.window = null, this.window_size = 0, this.prev = null, this.head = null, this.ins_h = 0, this.hash_size = 0, this.hash_bits = 0, this.hash_mask = 0, this.hash_shift = 0, this.block_start = 0, this.match_length = 0, this.prev_match = 0, this.match_available = 0, this.strstart = 0, this.match_start = 0, this.lookahead = 0, this.prev_length = 0, this.max_chain_length = 0, this.max_lazy_match = 0, this.level = 0, this.strategy = 0, this.good_match = 0, this.nice_match = 0, this.dyn_ltree = new sa.Buf16(1146), this.dyn_dtree = new sa.Buf16(122), this.bl_tree = new sa.Buf16(78), Ya(this.dyn_ltree), Ya(this.dyn_dtree), Ya(this.bl_tree), this.l_desc = null, this.d_desc = null, this.bl_desc = null, this.bl_count = new sa.Buf16(16), this.heap = new sa.Buf16(573), Ya(this.heap), this.heap_len = 0, this.heap_max = 0, this.depth = new sa.Buf16(573), Ya(this.depth), this.l_buf = 0, this.lit_bufsize = 0, this.last_lit = 0, this.d_buf = 0, this.opt_len = 0, this.static_len = 0, this.matches = 0, this.insert = 0, this.bi_buf = 0, this.bi_valid = 0
}

function hh(e) {
    var t;
    return e && e.state ? (e.total_in = e.total_out = 0, e.data_type = 2, (t = e.state).pending = 0, t.pending_out = 0, t.wrap < 0 && (t.wrap = -t.wrap), t.status = t.wrap ? 42 : 113, e.adler = 2 === t.wrap ? 0 : 1, t.last_flush = 0, ca._tr_init(t), 0) : Ga(e, -2)
}

function uh(e) {
    var t, r = hh(e);
    return 0 === r && ((t = e.state).window_size = 2 * t.w_size, Ya(t.head), t.max_lazy_match = Wa[t.level].max_lazy, t.good_match = Wa[t.level].good_length, t.nice_match = Wa[t.level].nice_length, t.max_chain_length = Wa[t.level].max_chain, t.strstart = 0, t.block_start = 0, t.lookahead = 0, t.insert = 0, t.match_length = t.prev_length = 2, t.match_available = 0, t.ins_h = 0), r
}

function ch(e, t, r, n, i, s) {
    if (!e) return -2;
    var o = 1;
    if (-1 === t && (t = 6), n < 0 ? (o = 0, n = -n) : n > 15 && (o = 2, n -= 16), i < 1 || i > 9 || 8 !== r || n < 8 || n > 15 || t < 0 || t > 9 || s < 0 || s > 4) return Ga(e, -2);
    8 === n && (n = 9);
    var a = new ah;
    return e.state = a, a.strm = e, a.wrap = o, a.gzhead = null, a.w_bits = n, a.w_size = 1 << a.w_bits, a.w_mask = a.w_size - 1, a.hash_bits = i + 7, a.hash_size = 1 << a.hash_bits, a.hash_mask = a.hash_size - 1, a.hash_shift = ~~((a.hash_bits + 3 - 1) / 3), a.window = new sa.Buf8(2 * a.w_size), a.head = new sa.Buf16(a.hash_size), a.prev = new sa.Buf16(a.w_size), a.lit_bufsize = 1 << i + 6, a.pending_buf_size = 4 * a.lit_bufsize, a.pending_buf = new sa.Buf8(a.pending_buf_size), a.d_buf = 1 * a.lit_bufsize, a.l_buf = 3 * a.lit_bufsize, a.level = t, a.strategy = s, a.method = r, uh(e)
}

Wa = [new oh(0, 0, 0, 0, (function (e, t) {
    var r = 65535;
    for (r > e.pending_buf_size - 5 && (r = e.pending_buf_size - 5); ;) {
        if (e.lookahead <= 1) {
            if (nh(e), 0 === e.lookahead && 0 === t) return 1;
            if (0 === e.lookahead) break
        }
        e.strstart += e.lookahead, e.lookahead = 0;
        var n = e.block_start + r;
        if ((0 === e.strstart || e.strstart >= n) && (e.lookahead = e.strstart - n, e.strstart = n, Qa(e, !1), 0 === e.strm.avail_out)) return 1;
        if (e.strstart - e.block_start >= e.w_size - 262 && (Qa(e, !1), 0 === e.strm.avail_out)) return 1
    }
    return e.insert = 0, 4 === t ? (Qa(e, !0), 0 === e.strm.avail_out ? 3 : 4) : (e.strstart > e.block_start && (Qa(e, !1), e.strm.avail_out), 1)
})), new oh(4, 4, 8, 4, ih), new oh(4, 5, 16, 8, ih), new oh(4, 6, 32, 32, ih), new oh(4, 4, 16, 16, sh), new oh(8, 16, 32, 32, sh), new oh(8, 16, 128, 128, sh), new oh(8, 32, 128, 256, sh), new oh(32, 128, 258, 1024, sh), new oh(32, 258, 258, 4096, sh)], Ka.deflateInit2 = ch, Ka.deflateSetHeader = function (e, t) {
    return e && e.state ? 2 !== e.state.wrap ? -2 : (e.state.gzhead = t, 0) : -2
}, Ka.deflate = function (e, t) {
    var r, n, i, s;
    if (!e || !e.state || t > 5 || t < 0) return e ? Ga(e, -2) : -2;
    if (n = e.state, !e.output || !e.input && 0 !== e.avail_in || 666 === n.status && 4 !== t) return Ga(e, 0 === e.avail_out ? -5 : -2);
    if (n.strm = e, r = n.last_flush, n.last_flush = t, 42 === n.status) if (2 === n.wrap) e.adler = 0, eh(n, 31), eh(n, 139), eh(n, 8), n.gzhead ? (eh(n, (n.gzhead.text ? 1 : 0) + (n.gzhead.hcrc ? 2 : 0) + (n.gzhead.extra ? 4 : 0) + (n.gzhead.name ? 8 : 0) + (n.gzhead.comment ? 16 : 0)), eh(n, 255 & n.gzhead.time), eh(n, n.gzhead.time >> 8 & 255), eh(n, n.gzhead.time >> 16 & 255), eh(n, n.gzhead.time >> 24 & 255), eh(n, 9 === n.level ? 2 : n.strategy >= 2 || n.level < 2 ? 4 : 0), eh(n, 255 & n.gzhead.os), n.gzhead.extra && n.gzhead.extra.length && (eh(n, 255 & n.gzhead.extra.length), eh(n, n.gzhead.extra.length >> 8 & 255)), n.gzhead.hcrc && (e.adler = Va(e.adler, n.pending_buf, n.pending, 0)), n.gzindex = 0, n.status = 69) : (eh(n, 0), eh(n, 0), eh(n, 0), eh(n, 0), eh(n, 0), eh(n, 9 === n.level ? 2 : n.strategy >= 2 || n.level < 2 ? 4 : 0), eh(n, 3), n.status = 113); else {
        var o = 8 + (n.w_bits - 8 << 4) << 8;
        o |= (n.strategy >= 2 || n.level < 2 ? 0 : n.level < 6 ? 1 : 6 === n.level ? 2 : 3) << 6, 0 !== n.strstart && (o |= 32), o += 31 - o % 31, n.status = 113, th(n, o), 0 !== n.strstart && (th(n, e.adler >>> 16), th(n, 65535 & e.adler)), e.adler = 1
    }
    if (69 === n.status) if (n.gzhead.extra) {
        for (i = n.pending; n.gzindex < (65535 & n.gzhead.extra.length) && (n.pending !== n.pending_buf_size || (n.gzhead.hcrc && n.pending > i && (e.adler = Va(e.adler, n.pending_buf, n.pending - i, i)), Ja(e), i = n.pending, n.pending !== n.pending_buf_size));) eh(n, 255 & n.gzhead.extra[n.gzindex]), n.gzindex++;
        n.gzhead.hcrc && n.pending > i && (e.adler = Va(e.adler, n.pending_buf, n.pending - i, i)), n.gzindex === n.gzhead.extra.length && (n.gzindex = 0, n.status = 73)
    } else n.status = 73;
    if (73 === n.status) if (n.gzhead.name) {
        i = n.pending;
        do {
            if (n.pending === n.pending_buf_size && (n.gzhead.hcrc && n.pending > i && (e.adler = Va(e.adler, n.pending_buf, n.pending - i, i)), Ja(e), i = n.pending, n.pending === n.pending_buf_size)) {
                s = 1;
                break
            }
            s = n.gzindex < n.gzhead.name.length ? 255 & n.gzhead.name.charCodeAt(n.gzindex++) : 0, eh(n, s)
        } while (0 !== s);
        n.gzhead.hcrc && n.pending > i && (e.adler = Va(e.adler, n.pending_buf, n.pending - i, i)), 0 === s && (n.gzindex = 0, n.status = 91)
    } else n.status = 91;
    if (91 === n.status) if (n.gzhead.comment) {
        i = n.pending;
        do {
            if (n.pending === n.pending_buf_size && (n.gzhead.hcrc && n.pending > i && (e.adler = Va(e.adler, n.pending_buf, n.pending - i, i)), Ja(e), i = n.pending, n.pending === n.pending_buf_size)) {
                s = 1;
                break
            }
            s = n.gzindex < n.gzhead.comment.length ? 255 & n.gzhead.comment.charCodeAt(n.gzindex++) : 0, eh(n, s)
        } while (0 !== s);
        n.gzhead.hcrc && n.pending > i && (e.adler = Va(e.adler, n.pending_buf, n.pending - i, i)), 0 === s && (n.status = 103)
    } else n.status = 103;
    if (103 === n.status && (n.gzhead.hcrc ? (n.pending + 2 > n.pending_buf_size && Ja(e), n.pending + 2 <= n.pending_buf_size && (eh(n, 255 & e.adler), eh(n, e.adler >> 8 & 255), e.adler = 0, n.status = 113)) : n.status = 113), 0 !== n.pending) {
        if (Ja(e), 0 === e.avail_out) return n.last_flush = -1, 0
    } else if (0 === e.avail_in && Xa(t) <= Xa(r) && 4 !== t) return Ga(e, -5);
    if (666 === n.status && 0 !== e.avail_in) return Ga(e, -5);
    if (0 !== e.avail_in || 0 !== n.lookahead || 0 !== t && 666 !== n.status) {
        var a = 2 === n.strategy ? function (e, t) {
            for (var r; ;) {
                if (0 === e.lookahead && (nh(e), 0 === e.lookahead)) {
                    if (0 === t) return 1;
                    break
                }
                if (e.match_length = 0, r = ca._tr_tally(e, 0, e.window[e.strstart]), e.lookahead--, e.strstart++, r && (Qa(e, !1), 0 === e.strm.avail_out)) return 1
            }
            return e.insert = 0, 4 === t ? (Qa(e, !0), 0 === e.strm.avail_out ? 3 : 4) : e.last_lit && (Qa(e, !1), 0 === e.strm.avail_out) ? 1 : 2
        }(n, t) : 3 === n.strategy ? function (e, t) {
            for (var r, n, i, s, o = e.window; ;) {
                if (e.lookahead <= 258) {
                    if (nh(e), e.lookahead <= 258 && 0 === t) return 1;
                    if (0 === e.lookahead) break
                }
                if (e.match_length = 0, e.lookahead >= 3 && e.strstart > 0 && (n = o[i = e.strstart - 1]) === o[++i] && n === o[++i] && n === o[++i]) {
                    s = e.strstart + 258;
                    do {
                    } while (n === o[++i] && n === o[++i] && n === o[++i] && n === o[++i] && n === o[++i] && n === o[++i] && n === o[++i] && n === o[++i] && i < s);
                    e.match_length = 258 - (s - i), e.match_length > e.lookahead && (e.match_length = e.lookahead)
                }
                if (e.match_length >= 3 ? (r = ca._tr_tally(e, 1, e.match_length - 3), e.lookahead -= e.match_length, e.strstart += e.match_length, e.match_length = 0) : (r = ca._tr_tally(e, 0, e.window[e.strstart]), e.lookahead--, e.strstart++), r && (Qa(e, !1), 0 === e.strm.avail_out)) return 1
            }
            return e.insert = 0, 4 === t ? (Qa(e, !0), 0 === e.strm.avail_out ? 3 : 4) : e.last_lit && (Qa(e, !1), 0 === e.strm.avail_out) ? 1 : 2
        }(n, t) : Wa[n.level].func(n, t);
        if (3 !== a && 4 !== a || (n.status = 666), 1 === a || 3 === a) return 0 === e.avail_out && (n.last_flush = -1), 0;
        if (2 === a && (1 === t ? ca._tr_align(n) : 5 !== t && (ca._tr_stored_block(n, 0, 0, !1), 3 === t && (Ya(n.head), 0 === n.lookahead && (n.strstart = 0, n.block_start = 0, n.insert = 0))), Ja(e), 0 === e.avail_out)) return n.last_flush = -1, 0
    }
    return 4 !== t ? 0 : n.wrap <= 0 ? 1 : (2 === n.wrap ? (eh(n, 255 & e.adler), eh(n, e.adler >> 8 & 255), eh(n, e.adler >> 16 & 255), eh(n, e.adler >> 24 & 255), eh(n, 255 & e.total_in), eh(n, e.total_in >> 8 & 255), eh(n, e.total_in >> 16 & 255), eh(n, e.total_in >> 24 & 255)) : (th(n, e.adler >>> 16), th(n, 65535 & e.adler)), Ja(e), n.wrap > 0 && (n.wrap = -n.wrap), 0 !== n.pending ? 0 : 1)
}, Ka.deflateEnd = function (e) {
    var t;
    return e && e.state ? 42 !== (t = e.state.status) && 69 !== t && 73 !== t && 91 !== t && 103 !== t && 113 !== t && 666 !== t ? Ga(e, -2) : (e.state = null, 113 === t ? Ga(e, -3) : 0) : -2
}, Ka.deflateSetDictionary = function (e, t) {
    var r, n, i, s, o, a, h, u, c = t.length;
    if (!e || !e.state) return -2;
    if (2 === (s = (r = e.state).wrap) || 1 === s && 42 !== r.status || r.lookahead) return -2;
    for (1 === s && (e.adler = qa(e.adler, t, c, 0)), r.wrap = 0, c >= r.w_size && (0 === s && (Ya(r.head), r.strstart = 0, r.block_start = 0, r.insert = 0), u = new sa.Buf8(r.w_size), sa.arraySet(u, t, c - r.w_size, r.w_size, 0), t = u, c = r.w_size), o = e.avail_in, a = e.next_in, h = e.input, e.avail_in = c, e.next_in = 0, e.input = t, nh(r); r.lookahead >= 3;) {
        n = r.strstart, i = r.lookahead - 2;
        do {
            r.ins_h = (r.ins_h << r.hash_shift ^ r.window[n + 3 - 1]) & r.hash_mask, r.prev[n & r.w_mask] = r.head[r.ins_h], r.head[r.ins_h] = n, n++
        } while (--i);
        r.strstart = n, r.lookahead = 2, nh(r)
    }
    return r.strstart += r.lookahead, r.block_start = r.strstart, r.insert = r.lookahead, r.lookahead = 0, r.match_length = r.prev_length = 2, r.match_available = 0, e.next_in = a, e.input = h, e.avail_in = o, r.wrap = s, 0
};
var dh = {}, lh = !0, fh = !0;
try {
    String.fromCharCode.apply(null, [0])
} catch (rc) {
    lh = !1
}
try {
    String.fromCharCode.apply(null, new Uint8Array(1))
} catch (rc) {
    fh = !1
}
for (var ph = new sa.Buf8(256), mh = 0; mh < 256; mh++) ph[mh] = mh >= 252 ? 6 : mh >= 248 ? 5 : mh >= 240 ? 4 : mh >= 224 ? 3 : mh >= 192 ? 2 : 1;

function gh(e, t) {
    if (t < 65534 && (e.subarray && fh || !e.subarray && lh)) return String.fromCharCode.apply(null, sa.shrinkBuf(e, t));
    for (var r = "", n = 0; n < t; n++) r += String.fromCharCode(e[n]);
    return r
}

ph[254] = ph[254] = 1, dh.string2buf = function (e) {
    var t, r, n, i, s, o = e.length, a = 0;
    for (i = 0; i < o; i++) 55296 == (64512 & (r = e.charCodeAt(i))) && i + 1 < o && 56320 == (64512 & (n = e.charCodeAt(i + 1))) && (r = 65536 + (r - 55296 << 10) + (n - 56320), i++), a += r < 128 ? 1 : r < 2048 ? 2 : r < 65536 ? 3 : 4;
    for (t = new sa.Buf8(a), s = 0, i = 0; s < a; i++) 55296 == (64512 & (r = e.charCodeAt(i))) && i + 1 < o && 56320 == (64512 & (n = e.charCodeAt(i + 1))) && (r = 65536 + (r - 55296 << 10) + (n - 56320), i++), r < 128 ? t[s++] = r : r < 2048 ? (t[s++] = 192 | r >>> 6, t[s++] = 128 | 63 & r) : r < 65536 ? (t[s++] = 224 | r >>> 12, t[s++] = 128 | r >>> 6 & 63, t[s++] = 128 | 63 & r) : (t[s++] = 240 | r >>> 18, t[s++] = 128 | r >>> 12 & 63, t[s++] = 128 | r >>> 6 & 63, t[s++] = 128 | 63 & r);
    return t
}, dh.buf2binstring = function (e) {
    return gh(e, e.length)
}, dh.binstring2buf = function (e) {
    for (var t = new sa.Buf8(e.length), r = 0, n = t.length; r < n; r++) t[r] = e.charCodeAt(r);
    return t
}, dh.buf2string = function (e, t) {
    var r, n, i, s, o = t || e.length, a = new Array(2 * o);
    for (n = 0, r = 0; r < o;) if ((i = e[r++]) < 128) a[n++] = i; else if ((s = ph[i]) > 4) a[n++] = 65533, r += s - 1; else {
        for (i &= 2 === s ? 31 : 3 === s ? 15 : 7; s > 1 && r < o;) i = i << 6 | 63 & e[r++], s--;
        s > 1 ? a[n++] = 65533 : i < 65536 ? a[n++] = i : (i -= 65536, a[n++] = 55296 | i >> 10 & 1023, a[n++] = 56320 | 1023 & i)
    }
    return gh(a, n)
}, dh.utf8border = function (e, t) {
    var r;
    for ((t = t || e.length) > e.length && (t = e.length), r = t - 1; r >= 0 && 128 == (192 & e[r]);) r--;
    return r < 0 || 0 === r ? t : r + ph[e[r]] > t ? r : t
};
var _h = function () {
    this.input = null, this.next_in = 0, this.avail_in = 0, this.total_in = 0, this.output = null, this.next_out = 0, this.avail_out = 0, this.total_out = 0, this.msg = "", this.state = null, this.data_type = 2, this.adler = 0
}, yh = {}, bh = Object.prototype.toString;

function vh(e) {
    if (!(this instanceof vh)) return new vh(e);
    this.options = sa.assign({
        level: -1,
        method: 8,
        chunkSize: 16384,
        windowBits: 15,
        memLevel: 8,
        strategy: 0,
        to: ""
    }, e || {});
    var t = this.options;
    t.raw && t.windowBits > 0 ? t.windowBits = -t.windowBits : t.gzip && t.windowBits > 0 && t.windowBits < 16 && (t.windowBits += 16), this.err = 0, this.msg = "", this.ended = !1, this.chunks = [], this.strm = new _h, this.strm.avail_out = 0;
    var r = Ka.deflateInit2(this.strm, t.level, t.method, t.windowBits, t.memLevel, t.strategy);
    if (0 !== r) throw new Error($a[r]);
    if (t.header && Ka.deflateSetHeader(this.strm, t.header), t.dictionary) {
        var n;
        if (n = "string" == typeof t.dictionary ? dh.string2buf(t.dictionary) : "[object ArrayBuffer]" === bh.call(t.dictionary) ? new Uint8Array(t.dictionary) : t.dictionary, 0 !== (r = Ka.deflateSetDictionary(this.strm, n))) throw new Error($a[r]);
        this._dict_set = !0
    }
}

function wh(e, t) {
    var r = new vh(t);
    if (r.push(e, !0), r.err) throw r.msg || $a[r.err];
    return r.result
}

vh.prototype.push = function (e, t) {
    var r, n, i = this.strm, s = this.options.chunkSize;
    if (this.ended) return !1;
    n = t === ~~t ? t : !0 === t ? 4 : 0, "string" == typeof e ? i.input = dh.string2buf(e) : "[object ArrayBuffer]" === bh.call(e) ? i.input = new Uint8Array(e) : i.input = e, i.next_in = 0, i.avail_in = i.input.length;
    do {
        if (0 === i.avail_out && (i.output = new sa.Buf8(s), i.next_out = 0, i.avail_out = s), 1 !== (r = Ka.deflate(i, n)) && 0 !== r) return this.onEnd(r), this.ended = !0, !1;
        0 !== i.avail_out && (0 !== i.avail_in || 4 !== n && 2 !== n) || ("string" === this.options.to ? this.onData(dh.buf2binstring(sa.shrinkBuf(i.output, i.next_out))) : this.onData(sa.shrinkBuf(i.output, i.next_out)))
    } while ((i.avail_in > 0 || 0 === i.avail_out) && 1 !== r);
    return 4 === n ? (r = Ka.deflateEnd(this.strm), this.onEnd(r), this.ended = !0, 0 === r) : 2 !== n || (this.onEnd(0), i.avail_out = 0, !0)
}, vh.prototype.onData = function (e) {
    this.chunks.push(e)
}, vh.prototype.onEnd = function (e) {
    0 === e && ("string" === this.options.to ? this.result = this.chunks.join("") : this.result = sa.flattenChunks(this.chunks)), this.chunks = [], this.err = e, this.msg = this.strm.msg
}, yh.Deflate = vh, yh.deflate = wh, yh.deflateRaw = function (e, t) {
    return (t = t || {}).raw = !0, wh(e, t)
}, yh.gzip = function (e, t) {
    return (t = t || {}).gzip = !0, wh(e, t)
};
var Eh = function (e, t) {
        var r, n, i, s, o, a, h, u, c, d, l, f, p, m, g, _, y, b, v, w, E, k, S, C, x;
        r = e.state, n = e.next_in, C = e.input, i = n + (e.avail_in - 5), s = e.next_out, x = e.output, o = s - (t - e.avail_out), a = s + (e.avail_out - 257), h = r.dmax, u = r.wsize, c = r.whave, d = r.wnext, l = r.window, f = r.hold, p = r.bits, m = r.lencode, g = r.distcode, _ = (1 << r.lenbits) - 1, y = (1 << r.distbits) - 1;
        e:do {
            p < 15 && (f += C[n++] << p, p += 8, f += C[n++] << p, p += 8), b = m[f & _];
            t:for (; ;) {
                if (f >>>= v = b >>> 24, p -= v, 0 == (v = b >>> 16 & 255)) x[s++] = 65535 & b; else {
                    if (!(16 & v)) {
                        if (0 == (64 & v)) {
                            b = m[(65535 & b) + (f & (1 << v) - 1)];
                            continue t
                        }
                        if (32 & v) {
                            r.mode = 12;
                            break e
                        }
                        e.msg = "invalid literal/length code", r.mode = 30;
                        break e
                    }
                    w = 65535 & b, (v &= 15) && (p < v && (f += C[n++] << p, p += 8), w += f & (1 << v) - 1, f >>>= v, p -= v), p < 15 && (f += C[n++] << p, p += 8, f += C[n++] << p, p += 8), b = g[f & y];
                    r:for (; ;) {
                        if (f >>>= v = b >>> 24, p -= v, !(16 & (v = b >>> 16 & 255))) {
                            if (0 == (64 & v)) {
                                b = g[(65535 & b) + (f & (1 << v) - 1)];
                                continue r
                            }
                            e.msg = "invalid distance code", r.mode = 30;
                            break e
                        }
                        if (E = 65535 & b, p < (v &= 15) && (f += C[n++] << p, (p += 8) < v && (f += C[n++] << p, p += 8)), (E += f & (1 << v) - 1) > h) {
                            e.msg = "invalid distance too far back", r.mode = 30;
                            break e
                        }
                        if (f >>>= v, p -= v, E > (v = s - o)) {
                            if ((v = E - v) > c && r.sane) {
                                e.msg = "invalid distance too far back", r.mode = 30;
                                break e
                            }
                            if (k = 0, S = l, 0 === d) {
                                if (k += u - v, v < w) {
                                    w -= v;
                                    do {
                                        x[s++] = l[k++]
                                    } while (--v);
                                    k = s - E, S = x
                                }
                            } else if (d < v) {
                                if (k += u + d - v, (v -= d) < w) {
                                    w -= v;
                                    do {
                                        x[s++] = l[k++]
                                    } while (--v);
                                    if (k = 0, d < w) {
                                        w -= v = d;
                                        do {
                                            x[s++] = l[k++]
                                        } while (--v);
                                        k = s - E, S = x
                                    }
                                }
                            } else if (k += d - v, v < w) {
                                w -= v;
                                do {
                                    x[s++] = l[k++]
                                } while (--v);
                                k = s - E, S = x
                            }
                            for (; w > 2;) x[s++] = S[k++], x[s++] = S[k++], x[s++] = S[k++], w -= 3;
                            w && (x[s++] = S[k++], w > 1 && (x[s++] = S[k++]))
                        } else {
                            k = s - E;
                            do {
                                x[s++] = x[k++], x[s++] = x[k++], x[s++] = x[k++], w -= 3
                            } while (w > 2);
                            w && (x[s++] = x[k++], w > 1 && (x[s++] = x[k++]))
                        }
                        break
                    }
                }
                break
            }
        } while (n < i && s < a);
        n -= w = p >> 3, f &= (1 << (p -= w << 3)) - 1, e.next_in = n, e.next_out = s, e.avail_in = n < i ? i - n + 5 : 5 - (n - i), e.avail_out = s < a ? a - s + 257 : 257 - (s - a), r.hold = f, r.bits = p
    },
    kh = [3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 15, 17, 19, 23, 27, 31, 35, 43, 51, 59, 67, 83, 99, 115, 131, 163, 195, 227, 258, 0, 0],
    Sh = [16, 16, 16, 16, 16, 16, 16, 16, 17, 17, 17, 17, 18, 18, 18, 18, 19, 19, 19, 19, 20, 20, 20, 20, 21, 21, 21, 21, 16, 72, 78],
    Ch = [1, 2, 3, 4, 5, 7, 9, 13, 17, 25, 33, 49, 65, 97, 129, 193, 257, 385, 513, 769, 1025, 1537, 2049, 3073, 4097, 6145, 8193, 12289, 16385, 24577, 0, 0],
    xh = [16, 16, 16, 16, 17, 17, 18, 18, 19, 19, 20, 20, 21, 21, 22, 22, 23, 23, 24, 24, 25, 25, 26, 26, 27, 27, 28, 28, 29, 29, 64, 64],
    Ah = function (e, t, r, n, i, s, o, a) {
        var h, u, c, d, l, f, p, m, g, _ = a.bits, y = 0, b = 0, v = 0, w = 0, E = 0, k = 0, S = 0, C = 0, x = 0, A = 0,
            T = null, I = 0, R = new sa.Buf16(16), B = new sa.Buf16(16), L = null, O = 0;
        for (y = 0; y <= 15; y++) R[y] = 0;
        for (b = 0; b < n; b++) R[t[r + b]]++;
        for (E = _, w = 15; w >= 1 && 0 === R[w]; w--) ;
        if (E > w && (E = w), 0 === w) return i[s++] = 20971520, i[s++] = 20971520, a.bits = 1, 0;
        for (v = 1; v < w && 0 === R[v]; v++) ;
        for (E < v && (E = v), C = 1, y = 1; y <= 15; y++) if (C <<= 1, (C -= R[y]) < 0) return -1;
        if (C > 0 && (0 === e || 1 !== w)) return -1;
        for (B[1] = 0, y = 1; y < 15; y++) B[y + 1] = B[y] + R[y];
        for (b = 0; b < n; b++) 0 !== t[r + b] && (o[B[t[r + b]]++] = b);
        if (0 === e ? (T = L = o, f = 19) : 1 === e ? (T = kh, I -= 257, L = Sh, O -= 257, f = 256) : (T = Ch, L = xh, f = -1), A = 0, b = 0, y = v, l = s, k = E, S = 0, c = -1, d = (x = 1 << E) - 1, 1 === e && x > 852 || 2 === e && x > 592) return 1;
        for (; ;) {
            p = y - S, o[b] < f ? (m = 0, g = o[b]) : o[b] > f ? (m = L[O + o[b]], g = T[I + o[b]]) : (m = 96, g = 0), h = 1 << y - S, v = u = 1 << k;
            do {
                i[l + (A >> S) + (u -= h)] = p << 24 | m << 16 | g | 0
            } while (0 !== u);
            for (h = 1 << y - 1; A & h;) h >>= 1;
            if (0 !== h ? (A &= h - 1, A += h) : A = 0, b++, 0 == --R[y]) {
                if (y === w) break;
                y = t[r + o[b]]
            }
            if (y > E && (A & d) !== c) {
                for (0 === S && (S = E), l += v, C = 1 << (k = y - S); k + S < w && !((C -= R[k + S]) <= 0);) k++, C <<= 1;
                if (x += 1 << k, 1 === e && x > 852 || 2 === e && x > 592) return 1;
                i[c = A & d] = E << 24 | k << 16 | l - s | 0
            }
        }
        return 0 !== A && (i[l + A] = y - S << 24 | 64 << 16 | 0), a.bits = E, 0
    }, Th = {};

function Ih(e) {
    return (e >>> 24 & 255) + (e >>> 8 & 65280) + ((65280 & e) << 8) + ((255 & e) << 24)
}

function Rh() {
    this.mode = 0, this.last = !1, this.wrap = 0, this.havedict = !1, this.flags = 0, this.dmax = 0, this.check = 0, this.total = 0, this.head = null, this.wbits = 0, this.wsize = 0, this.whave = 0, this.wnext = 0, this.window = null, this.hold = 0, this.bits = 0, this.length = 0, this.offset = 0, this.extra = 0, this.lencode = null, this.distcode = null, this.lenbits = 0, this.distbits = 0, this.ncode = 0, this.nlen = 0, this.ndist = 0, this.have = 0, this.next = null, this.lens = new sa.Buf16(320), this.work = new sa.Buf16(288), this.lendyn = null, this.distdyn = null, this.sane = 0, this.back = 0, this.was = 0
}

function Bh(e) {
    var t;
    return e && e.state ? (t = e.state, e.total_in = e.total_out = t.total = 0, e.msg = "", t.wrap && (e.adler = 1 & t.wrap), t.mode = 1, t.last = 0, t.havedict = 0, t.dmax = 32768, t.head = null, t.hold = 0, t.bits = 0, t.lencode = t.lendyn = new sa.Buf32(852), t.distcode = t.distdyn = new sa.Buf32(592), t.sane = 1, t.back = -1, 0) : -2
}

function Lh(e) {
    var t;
    return e && e.state ? ((t = e.state).wsize = 0, t.whave = 0, t.wnext = 0, Bh(e)) : -2
}

function Oh(e, t) {
    var r, n;
    return e && e.state ? (n = e.state, t < 0 ? (r = 0, t = -t) : (r = 1 + (t >> 4), t < 48 && (t &= 15)), t && (t < 8 || t > 15) ? -2 : (null !== n.window && n.wbits !== t && (n.window = null), n.wrap = r, n.wbits = t, Lh(e))) : -2
}

function Uh(e, t) {
    var r, n;
    return e ? (n = new Rh, e.state = n, n.window = null, 0 !== (r = Oh(e, t)) && (e.state = null), r) : -2
}

var Ph, Mh, Dh = !0;

function Nh(e) {
    if (Dh) {
        var t;
        for (Ph = new sa.Buf32(512), Mh = new sa.Buf32(32), t = 0; t < 144;) e.lens[t++] = 8;
        for (; t < 256;) e.lens[t++] = 9;
        for (; t < 280;) e.lens[t++] = 7;
        for (; t < 288;) e.lens[t++] = 8;
        for (Ah(1, e.lens, 0, 288, Ph, 0, e.work, {bits: 9}), t = 0; t < 32;) e.lens[t++] = 5;
        Ah(2, e.lens, 0, 32, Mh, 0, e.work, {bits: 5}), Dh = !1
    }
    e.lencode = Ph, e.lenbits = 9, e.distcode = Mh, e.distbits = 5
}

function jh(e, t, r, n) {
    var i, s = e.state;
    return null === s.window && (s.wsize = 1 << s.wbits, s.wnext = 0, s.whave = 0, s.window = new sa.Buf8(s.wsize)), n >= s.wsize ? (sa.arraySet(s.window, t, r - s.wsize, s.wsize, 0), s.wnext = 0, s.whave = s.wsize) : ((i = s.wsize - s.wnext) > n && (i = n), sa.arraySet(s.window, t, r - n, i, s.wnext), (n -= i) ? (sa.arraySet(s.window, t, r - n, n, 0), s.wnext = n, s.whave = s.wsize) : (s.wnext += i, s.wnext === s.wsize && (s.wnext = 0), s.whave < s.wsize && (s.whave += i))), 0
}

Th.inflateInit2 = Uh, Th.inflate = function (e, t) {
    var r, n, i, s, o, a, h, u, c, d, l, f, p, m, g, _, y, b, v, w, E, k, S, C, x = 0, A = new sa.Buf8(4),
        T = [16, 17, 18, 0, 8, 7, 9, 6, 10, 5, 11, 4, 12, 3, 13, 2, 14, 1, 15];
    if (!e || !e.state || !e.output || !e.input && 0 !== e.avail_in) return -2;
    12 === (r = e.state).mode && (r.mode = 13), o = e.next_out, i = e.output, h = e.avail_out, s = e.next_in, n = e.input, a = e.avail_in, u = r.hold, c = r.bits, d = a, l = h, k = 0;
    e:for (; ;) switch (r.mode) {
        case 1:
            if (0 === r.wrap) {
                r.mode = 13;
                break
            }
            for (; c < 16;) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            if (2 & r.wrap && 35615 === u) {
                r.check = 0, A[0] = 255 & u, A[1] = u >>> 8 & 255, r.check = Va(r.check, A, 2, 0), u = 0, c = 0, r.mode = 2;
                break
            }
            if (r.flags = 0, r.head && (r.head.done = !1), !(1 & r.wrap) || (((255 & u) << 8) + (u >> 8)) % 31) {
                e.msg = "incorrect header check", r.mode = 30;
                break
            }
            if (8 != (15 & u)) {
                e.msg = "unknown compression method", r.mode = 30;
                break
            }
            if (c -= 4, E = 8 + (15 & (u >>>= 4)), 0 === r.wbits) r.wbits = E; else if (E > r.wbits) {
                e.msg = "invalid window size", r.mode = 30;
                break
            }
            r.dmax = 1 << E, e.adler = r.check = 1, r.mode = 512 & u ? 10 : 12, u = 0, c = 0;
            break;
        case 2:
            for (; c < 16;) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            if (r.flags = u, 8 != (255 & r.flags)) {
                e.msg = "unknown compression method", r.mode = 30;
                break
            }
            if (57344 & r.flags) {
                e.msg = "unknown header flags set", r.mode = 30;
                break
            }
            r.head && (r.head.text = u >> 8 & 1), 512 & r.flags && (A[0] = 255 & u, A[1] = u >>> 8 & 255, r.check = Va(r.check, A, 2, 0)), u = 0, c = 0, r.mode = 3;
        case 3:
            for (; c < 32;) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            r.head && (r.head.time = u), 512 & r.flags && (A[0] = 255 & u, A[1] = u >>> 8 & 255, A[2] = u >>> 16 & 255, A[3] = u >>> 24 & 255, r.check = Va(r.check, A, 4, 0)), u = 0, c = 0, r.mode = 4;
        case 4:
            for (; c < 16;) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            r.head && (r.head.xflags = 255 & u, r.head.os = u >> 8), 512 & r.flags && (A[0] = 255 & u, A[1] = u >>> 8 & 255, r.check = Va(r.check, A, 2, 0)), u = 0, c = 0, r.mode = 5;
        case 5:
            if (1024 & r.flags) {
                for (; c < 16;) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                r.length = u, r.head && (r.head.extra_len = u), 512 & r.flags && (A[0] = 255 & u, A[1] = u >>> 8 & 255, r.check = Va(r.check, A, 2, 0)), u = 0, c = 0
            } else r.head && (r.head.extra = null);
            r.mode = 6;
        case 6:
            if (1024 & r.flags && ((f = r.length) > a && (f = a), f && (r.head && (E = r.head.extra_len - r.length, r.head.extra || (r.head.extra = new Array(r.head.extra_len)), sa.arraySet(r.head.extra, n, s, f, E)), 512 & r.flags && (r.check = Va(r.check, n, f, s)), a -= f, s += f, r.length -= f), r.length)) break e;
            r.length = 0, r.mode = 7;
        case 7:
            if (2048 & r.flags) {
                if (0 === a) break e;
                f = 0;
                do {
                    E = n[s + f++], r.head && E && r.length < 65536 && (r.head.name += String.fromCharCode(E))
                } while (E && f < a);
                if (512 & r.flags && (r.check = Va(r.check, n, f, s)), a -= f, s += f, E) break e
            } else r.head && (r.head.name = null);
            r.length = 0, r.mode = 8;
        case 8:
            if (4096 & r.flags) {
                if (0 === a) break e;
                f = 0;
                do {
                    E = n[s + f++], r.head && E && r.length < 65536 && (r.head.comment += String.fromCharCode(E))
                } while (E && f < a);
                if (512 & r.flags && (r.check = Va(r.check, n, f, s)), a -= f, s += f, E) break e
            } else r.head && (r.head.comment = null);
            r.mode = 9;
        case 9:
            if (512 & r.flags) {
                for (; c < 16;) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                if (u !== (65535 & r.check)) {
                    e.msg = "header crc mismatch", r.mode = 30;
                    break
                }
                u = 0, c = 0
            }
            r.head && (r.head.hcrc = r.flags >> 9 & 1, r.head.done = !0), e.adler = r.check = 0, r.mode = 12;
            break;
        case 10:
            for (; c < 32;) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            e.adler = r.check = Ih(u), u = 0, c = 0, r.mode = 11;
        case 11:
            if (0 === r.havedict) return e.next_out = o, e.avail_out = h, e.next_in = s, e.avail_in = a, r.hold = u, r.bits = c, 2;
            e.adler = r.check = 1, r.mode = 12;
        case 12:
            if (5 === t || 6 === t) break e;
        case 13:
            if (r.last) {
                u >>>= 7 & c, c -= 7 & c, r.mode = 27;
                break
            }
            for (; c < 3;) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            switch (r.last = 1 & u, c -= 1, 3 & (u >>>= 1)) {
                case 0:
                    r.mode = 14;
                    break;
                case 1:
                    if (Nh(r), r.mode = 20, 6 === t) {
                        u >>>= 2, c -= 2;
                        break e
                    }
                    break;
                case 2:
                    r.mode = 17;
                    break;
                case 3:
                    e.msg = "invalid block type", r.mode = 30
            }
            u >>>= 2, c -= 2;
            break;
        case 14:
            for (u >>>= 7 & c, c -= 7 & c; c < 32;) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            if ((65535 & u) != (u >>> 16 ^ 65535)) {
                e.msg = "invalid stored block lengths", r.mode = 30;
                break
            }
            if (r.length = 65535 & u, u = 0, c = 0, r.mode = 15, 6 === t) break e;
        case 15:
            r.mode = 16;
        case 16:
            if (f = r.length) {
                if (f > a && (f = a), f > h && (f = h), 0 === f) break e;
                sa.arraySet(i, n, s, f, o), a -= f, s += f, h -= f, o += f, r.length -= f;
                break
            }
            r.mode = 12;
            break;
        case 17:
            for (; c < 14;) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            if (r.nlen = 257 + (31 & u), u >>>= 5, c -= 5, r.ndist = 1 + (31 & u), u >>>= 5, c -= 5, r.ncode = 4 + (15 & u), u >>>= 4, c -= 4, r.nlen > 286 || r.ndist > 30) {
                e.msg = "too many length or distance symbols", r.mode = 30;
                break
            }
            r.have = 0, r.mode = 18;
        case 18:
            for (; r.have < r.ncode;) {
                for (; c < 3;) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                r.lens[T[r.have++]] = 7 & u, u >>>= 3, c -= 3
            }
            for (; r.have < 19;) r.lens[T[r.have++]] = 0;
            if (r.lencode = r.lendyn, r.lenbits = 7, S = {bits: r.lenbits}, k = Ah(0, r.lens, 0, 19, r.lencode, 0, r.work, S), r.lenbits = S.bits, k) {
                e.msg = "invalid code lengths set", r.mode = 30;
                break
            }
            r.have = 0, r.mode = 19;
        case 19:
            for (; r.have < r.nlen + r.ndist;) {
                for (; _ = (x = r.lencode[u & (1 << r.lenbits) - 1]) >>> 16 & 255, y = 65535 & x, !((g = x >>> 24) <= c);) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                if (y < 16) u >>>= g, c -= g, r.lens[r.have++] = y; else {
                    if (16 === y) {
                        for (C = g + 2; c < C;) {
                            if (0 === a) break e;
                            a--, u += n[s++] << c, c += 8
                        }
                        if (u >>>= g, c -= g, 0 === r.have) {
                            e.msg = "invalid bit length repeat", r.mode = 30;
                            break
                        }
                        E = r.lens[r.have - 1], f = 3 + (3 & u), u >>>= 2, c -= 2
                    } else if (17 === y) {
                        for (C = g + 3; c < C;) {
                            if (0 === a) break e;
                            a--, u += n[s++] << c, c += 8
                        }
                        c -= g, E = 0, f = 3 + (7 & (u >>>= g)), u >>>= 3, c -= 3
                    } else {
                        for (C = g + 7; c < C;) {
                            if (0 === a) break e;
                            a--, u += n[s++] << c, c += 8
                        }
                        c -= g, E = 0, f = 11 + (127 & (u >>>= g)), u >>>= 7, c -= 7
                    }
                    if (r.have + f > r.nlen + r.ndist) {
                        e.msg = "invalid bit length repeat", r.mode = 30;
                        break
                    }
                    for (; f--;) r.lens[r.have++] = E
                }
            }
            if (30 === r.mode) break;
            if (0 === r.lens[256]) {
                e.msg = "invalid code -- missing end-of-block", r.mode = 30;
                break
            }
            if (r.lenbits = 9, S = {bits: r.lenbits}, k = Ah(1, r.lens, 0, r.nlen, r.lencode, 0, r.work, S), r.lenbits = S.bits, k) {
                e.msg = "invalid literal/lengths set", r.mode = 30;
                break
            }
            if (r.distbits = 6, r.distcode = r.distdyn, S = {bits: r.distbits}, k = Ah(2, r.lens, r.nlen, r.ndist, r.distcode, 0, r.work, S), r.distbits = S.bits, k) {
                e.msg = "invalid distances set", r.mode = 30;
                break
            }
            if (r.mode = 20, 6 === t) break e;
        case 20:
            r.mode = 21;
        case 21:
            if (a >= 6 && h >= 258) {
                e.next_out = o, e.avail_out = h, e.next_in = s, e.avail_in = a, r.hold = u, r.bits = c, Eh(e, l), o = e.next_out, i = e.output, h = e.avail_out, s = e.next_in, n = e.input, a = e.avail_in, u = r.hold, c = r.bits, 12 === r.mode && (r.back = -1);
                break
            }
            for (r.back = 0; _ = (x = r.lencode[u & (1 << r.lenbits) - 1]) >>> 16 & 255, y = 65535 & x, !((g = x >>> 24) <= c);) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            if (_ && 0 == (240 & _)) {
                for (b = g, v = _, w = y; _ = (x = r.lencode[w + ((u & (1 << b + v) - 1) >> b)]) >>> 16 & 255, y = 65535 & x, !(b + (g = x >>> 24) <= c);) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                u >>>= b, c -= b, r.back += b
            }
            if (u >>>= g, c -= g, r.back += g, r.length = y, 0 === _) {
                r.mode = 26;
                break
            }
            if (32 & _) {
                r.back = -1, r.mode = 12;
                break
            }
            if (64 & _) {
                e.msg = "invalid literal/length code", r.mode = 30;
                break
            }
            r.extra = 15 & _, r.mode = 22;
        case 22:
            if (r.extra) {
                for (C = r.extra; c < C;) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                r.length += u & (1 << r.extra) - 1, u >>>= r.extra, c -= r.extra, r.back += r.extra
            }
            r.was = r.length, r.mode = 23;
        case 23:
            for (; _ = (x = r.distcode[u & (1 << r.distbits) - 1]) >>> 16 & 255, y = 65535 & x, !((g = x >>> 24) <= c);) {
                if (0 === a) break e;
                a--, u += n[s++] << c, c += 8
            }
            if (0 == (240 & _)) {
                for (b = g, v = _, w = y; _ = (x = r.distcode[w + ((u & (1 << b + v) - 1) >> b)]) >>> 16 & 255, y = 65535 & x, !(b + (g = x >>> 24) <= c);) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                u >>>= b, c -= b, r.back += b
            }
            if (u >>>= g, c -= g, r.back += g, 64 & _) {
                e.msg = "invalid distance code", r.mode = 30;
                break
            }
            r.offset = y, r.extra = 15 & _, r.mode = 24;
        case 24:
            if (r.extra) {
                for (C = r.extra; c < C;) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                r.offset += u & (1 << r.extra) - 1, u >>>= r.extra, c -= r.extra, r.back += r.extra
            }
            if (r.offset > r.dmax) {
                e.msg = "invalid distance too far back", r.mode = 30;
                break
            }
            r.mode = 25;
        case 25:
            if (0 === h) break e;
            if (f = l - h, r.offset > f) {
                if ((f = r.offset - f) > r.whave && r.sane) {
                    e.msg = "invalid distance too far back", r.mode = 30;
                    break
                }
                f > r.wnext ? (f -= r.wnext, p = r.wsize - f) : p = r.wnext - f, f > r.length && (f = r.length), m = r.window
            } else m = i, p = o - r.offset, f = r.length;
            f > h && (f = h), h -= f, r.length -= f;
            do {
                i[o++] = m[p++]
            } while (--f);
            0 === r.length && (r.mode = 21);
            break;
        case 26:
            if (0 === h) break e;
            i[o++] = r.length, h--, r.mode = 21;
            break;
        case 27:
            if (r.wrap) {
                for (; c < 32;) {
                    if (0 === a) break e;
                    a--, u |= n[s++] << c, c += 8
                }
                if (l -= h, e.total_out += l, r.total += l, l && (e.adler = r.check = r.flags ? Va(r.check, i, l, o - l) : qa(r.check, i, l, o - l)), l = h, (r.flags ? u : Ih(u)) !== r.check) {
                    e.msg = "incorrect data check", r.mode = 30;
                    break
                }
                u = 0, c = 0
            }
            r.mode = 28;
        case 28:
            if (r.wrap && r.flags) {
                for (; c < 32;) {
                    if (0 === a) break e;
                    a--, u += n[s++] << c, c += 8
                }
                if (u !== (4294967295 & r.total)) {
                    e.msg = "incorrect length check", r.mode = 30;
                    break
                }
                u = 0, c = 0
            }
            r.mode = 29;
        case 29:
            k = 1;
            break e;
        case 30:
            k = -3;
            break e;
        case 31:
            return -4;
        case 32:
        default:
            return -2
    }
    return e.next_out = o, e.avail_out = h, e.next_in = s, e.avail_in = a, r.hold = u, r.bits = c, (r.wsize || l !== e.avail_out && r.mode < 30 && (r.mode < 27 || 4 !== t)) && jh(e, e.output, e.next_out, l - e.avail_out) ? (r.mode = 31, -4) : (d -= e.avail_in, l -= e.avail_out, e.total_in += d, e.total_out += l, r.total += l, r.wrap && l && (e.adler = r.check = r.flags ? Va(r.check, i, l, e.next_out - l) : qa(r.check, i, l, e.next_out - l)), e.data_type = r.bits + (r.last ? 64 : 0) + (12 === r.mode ? 128 : 0) + (20 === r.mode || 15 === r.mode ? 256 : 0), (0 === d && 0 === l || 4 === t) && 0 === k && (k = -5), k)
}, Th.inflateEnd = function (e) {
    if (!e || !e.state) return -2;
    var t = e.state;
    return t.window && (t.window = null), e.state = null, 0
}, Th.inflateGetHeader = function (e, t) {
    var r;
    return e && e.state ? 0 == (2 & (r = e.state).wrap) ? -2 : (r.head = t, t.done = !1, 0) : -2
}, Th.inflateSetDictionary = function (e, t) {
    var r, n = t.length;
    return e && e.state ? 0 !== (r = e.state).wrap && 11 !== r.mode ? -2 : 11 === r.mode && qa(1, t, n, 0) !== r.check ? -3 : jh(e, t, n, n) ? (r.mode = 31, -4) : (r.havedict = 1, 0) : -2
};
var Fh = {
    Z_NO_FLUSH: 0,
    Z_PARTIAL_FLUSH: 1,
    Z_SYNC_FLUSH: 2,
    Z_FULL_FLUSH: 3,
    Z_FINISH: 4,
    Z_BLOCK: 5,
    Z_TREES: 6,
    Z_OK: 0,
    Z_STREAM_END: 1,
    Z_NEED_DICT: 2,
    Z_ERRNO: -1,
    Z_STREAM_ERROR: -2,
    Z_DATA_ERROR: -3,
    Z_BUF_ERROR: -5,
    Z_NO_COMPRESSION: 0,
    Z_BEST_SPEED: 1,
    Z_BEST_COMPRESSION: 9,
    Z_DEFAULT_COMPRESSION: -1,
    Z_FILTERED: 1,
    Z_HUFFMAN_ONLY: 2,
    Z_RLE: 3,
    Z_FIXED: 4,
    Z_DEFAULT_STRATEGY: 0,
    Z_BINARY: 0,
    Z_TEXT: 1,
    Z_UNKNOWN: 2,
    Z_DEFLATED: 8
}, zh = function () {
    this.text = 0, this.time = 0, this.xflags = 0, this.os = 0, this.extra = null, this.extra_len = 0, this.name = "", this.comment = "", this.hcrc = 0, this.done = !1
}, Hh = {}, Wh = Object.prototype.toString;

function qh(e) {
    if (!(this instanceof qh)) return new qh(e);
    this.options = sa.assign({chunkSize: 16384, windowBits: 0, to: ""}, e || {});
    var t = this.options;
    t.raw && t.windowBits >= 0 && t.windowBits < 16 && (t.windowBits = -t.windowBits, 0 === t.windowBits && (t.windowBits = -15)), !(t.windowBits >= 0 && t.windowBits < 16) || e && e.windowBits || (t.windowBits += 32), t.windowBits > 15 && t.windowBits < 48 && 0 == (15 & t.windowBits) && (t.windowBits |= 15), this.err = 0, this.msg = "", this.ended = !1, this.chunks = [], this.strm = new _h, this.strm.avail_out = 0;
    var r = Th.inflateInit2(this.strm, t.windowBits);
    if (r !== Fh.Z_OK) throw new Error($a[r]);
    if (this.header = new zh, Th.inflateGetHeader(this.strm, this.header), t.dictionary && ("string" == typeof t.dictionary ? t.dictionary = dh.string2buf(t.dictionary) : "[object ArrayBuffer]" === Wh.call(t.dictionary) && (t.dictionary = new Uint8Array(t.dictionary)), t.raw && (r = Th.inflateSetDictionary(this.strm, t.dictionary)) !== Fh.Z_OK)) throw new Error($a[r])
}

function Zh(e, t) {
    var r = new qh(t);
    if (r.push(e, !0), r.err) throw r.msg || $a[r.err];
    return r.result
}

qh.prototype.push = function (e, t) {
    var r, n, i, s, o, a = this.strm, h = this.options.chunkSize, u = this.options.dictionary, c = !1;
    if (this.ended) return !1;
    n = t === ~~t ? t : !0 === t ? Fh.Z_FINISH : Fh.Z_NO_FLUSH, "string" == typeof e ? a.input = dh.binstring2buf(e) : "[object ArrayBuffer]" === Wh.call(e) ? a.input = new Uint8Array(e) : a.input = e, a.next_in = 0, a.avail_in = a.input.length;
    do {
        if (0 === a.avail_out && (a.output = new sa.Buf8(h), a.next_out = 0, a.avail_out = h), (r = Th.inflate(a, Fh.Z_NO_FLUSH)) === Fh.Z_NEED_DICT && u && (r = Th.inflateSetDictionary(this.strm, u)), r === Fh.Z_BUF_ERROR && !0 === c && (r = Fh.Z_OK, c = !1), r !== Fh.Z_STREAM_END && r !== Fh.Z_OK) return this.onEnd(r), this.ended = !0, !1;
        a.next_out && (0 !== a.avail_out && r !== Fh.Z_STREAM_END && (0 !== a.avail_in || n !== Fh.Z_FINISH && n !== Fh.Z_SYNC_FLUSH) || ("string" === this.options.to ? (i = dh.utf8border(a.output, a.next_out), s = a.next_out - i, o = dh.buf2string(a.output, i), a.next_out = s, a.avail_out = h - s, s && sa.arraySet(a.output, a.output, i, s, 0), this.onData(o)) : this.onData(sa.shrinkBuf(a.output, a.next_out)))), 0 === a.avail_in && 0 === a.avail_out && (c = !0)
    } while ((a.avail_in > 0 || 0 === a.avail_out) && r !== Fh.Z_STREAM_END);
    return r === Fh.Z_STREAM_END && (n = Fh.Z_FINISH), n === Fh.Z_FINISH ? (r = Th.inflateEnd(this.strm), this.onEnd(r), this.ended = !0, r === Fh.Z_OK) : n !== Fh.Z_SYNC_FLUSH || (this.onEnd(Fh.Z_OK), a.avail_out = 0, !0)
}, qh.prototype.onData = function (e) {
    this.chunks.push(e)
}, qh.prototype.onEnd = function (e) {
    e === Fh.Z_OK && ("string" === this.options.to ? this.result = this.chunks.join("") : this.result = sa.flattenChunks(this.chunks)), this.chunks = [], this.err = e, this.msg = this.strm.msg
}, Hh.Inflate = qh, Hh.inflate = Zh, Hh.inflateRaw = function (e, t) {
    return (t = t || {}).raw = !0, Zh(e, t)
}, Hh.ungzip = Zh;
var Vh = {}, $h = {};
(0, sa.assign)($h, yh, Hh, Fh), Vh = $h;
var Kh = {},
    Gh = "undefined" != typeof Uint8Array && "undefined" != typeof Uint16Array && "undefined" != typeof Uint32Array,
    Xh = n({}), Yh = r({}), Jh = Gh ? "uint8array" : "array";

function Qh(e, t) {
    Yh.call(this, "FlateWorker/" + e), this._pako = null, this._pakoAction = e, this._pakoOptions = t, this.meta = {}
}

Kh.magic = "\b\0", Xh.inherits(Qh, Yh), Qh.prototype.processChunk = function (e) {
    this.meta = e.meta, null === this._pako && this._createPako(), this._pako.push(Xh.transformTo(Jh, e.data), !1)
}, Qh.prototype.flush = function () {
    Yh.prototype.flush.call(this), null === this._pako && this._createPako(), this._pako.push([], !0)
}, Qh.prototype.cleanUp = function () {
    Yh.prototype.cleanUp.call(this), this._pako = null
}, Qh.prototype._createPako = function () {
    this._pako = new Vh[this._pakoAction]({raw: !0, level: this._pakoOptions.level || -1});
    var e = this;
    this._pako.onData = function (t) {
        e.push({data: t, meta: e.meta})
    }
}, Kh.compressWorker = function (e) {
    return new Qh("Deflate", e)
}, Kh.uncompressWorker = function () {
    return new Qh("Inflate", {})
};
var eu = {}, tu = r({});
eu.STORE = {
    magic: "\0\0", compressWorker: function (e) {
        return new tu("STORE compression")
    }, uncompressWorker: function () {
        return new tu("STORE decompression")
    }
}, eu.DEFLATE = Kh;
var ru = {
    LOCAL_FILE_HEADER: "PK\x03\x04",
    CENTRAL_FILE_HEADER: "PK\x01\x02",
    CENTRAL_DIRECTORY_END: "PK\x05\x06",
    ZIP64_CENTRAL_DIRECTORY_LOCATOR: "PK\x06\x07",
    ZIP64_CENTRAL_DIRECTORY_END: "PK\x06\x06",
    DATA_DESCRIPTOR: "PK\x07\b"
}, nu = {}, iu = n({}), su = r({}), ou = function (e, t) {
    var r, n = "";
    for (r = 0; r < t; r++) n += String.fromCharCode(255 & e), e >>>= 8;
    return n
}, au = function (e, t, r, n, i, s) {
    var o, a, h = e.file, u = e.compression, c = s !== Eo.utf8encode, d = iu.transformTo("string", s(h.name)),
        l = iu.transformTo("string", Eo.utf8encode(h.name)), f = h.comment, p = iu.transformTo("string", s(f)),
        m = iu.transformTo("string", Eo.utf8encode(f)), g = l.length !== h.name.length, _ = m.length !== f.length,
        y = "", b = "", v = "", w = h.dir, E = h.date, k = {crc32: 0, compressedSize: 0, uncompressedSize: 0};
    t && !r || (k.crc32 = e.crc32, k.compressedSize = e.compressedSize, k.uncompressedSize = e.uncompressedSize);
    var S = 0;
    t && (S |= 8), c || !g && !_ || (S |= 2048);
    var C, x, A = 0, T = 0;
    w && (A |= 16), "UNIX" === i ? (T = 798, A |= (x = C = h.unixPermissions, C || (x = w ? 16893 : 33204), (65535 & x) << 16)) : (T = 20, A |= 63 & (h.dosPermissions || 0)), o = E.getUTCHours(), o <<= 6, o |= E.getUTCMinutes(), o <<= 5, o |= E.getUTCSeconds() / 2, a = E.getUTCFullYear() - 1980, a <<= 4, a |= E.getUTCMonth() + 1, a <<= 5, a |= E.getUTCDate(), g && (b = ou(1, 1) + ou(Wo(d), 4) + l, y += "up" + ou(b.length, 2) + b), _ && (v = ou(1, 1) + ou(Wo(p), 4) + m, y += "uc" + ou(v.length, 2) + v);
    var I = "";
    return I += "\n\0", I += ou(S, 2), I += u.magic, I += ou(o, 2), I += ou(a, 2), I += ou(k.crc32, 4), I += ou(k.compressedSize, 4), I += ou(k.uncompressedSize, 4), I += ou(d.length, 2), I += ou(y.length, 2), {
        fileRecord: ru.LOCAL_FILE_HEADER + I + d + y,
        dirRecord: ru.CENTRAL_FILE_HEADER + ou(T, 2) + I + ou(p.length, 2) + "\0\0\0\0" + ou(A, 4) + ou(n, 4) + d + y + p
    }
}, hu = function (e) {
    return ru.DATA_DESCRIPTOR + ou(e.crc32, 4) + ou(e.compressedSize, 4) + ou(e.uncompressedSize, 4)
};

function uu(e, t, r, n) {
    su.call(this, "ZipFileWorker"), this.bytesWritten = 0, this.zipComment = t, this.zipPlatform = r, this.encodeFileName = n, this.streamFiles = e, this.accumulate = !1, this.contentBuffer = [], this.dirRecords = [], this.currentSourceOffset = 0, this.entriesCount = 0, this.currentFile = null, this._sources = []
}

iu.inherits(uu, su), uu.prototype.push = function (e) {
    var t = e.meta.percent || 0, r = this.entriesCount, n = this._sources.length;
    this.accumulate ? this.contentBuffer.push(e) : (this.bytesWritten += e.data.length, su.prototype.push.call(this, {
        data: e.data,
        meta: {currentFile: this.currentFile, percent: r ? (t + 100 * (r - n - 1)) / r : 100}
    }))
}, uu.prototype.openedSource = function (e) {
    this.currentSourceOffset = this.bytesWritten, this.currentFile = e.file.name;
    var t = this.streamFiles && !e.file.dir;
    if (t) {
        var r = au(e, t, !1, this.currentSourceOffset, this.zipPlatform, this.encodeFileName);
        this.push({data: r.fileRecord, meta: {percent: 0}})
    } else this.accumulate = !0
}, uu.prototype.closedSource = function (e) {
    this.accumulate = !1;
    var t = this.streamFiles && !e.file.dir,
        r = au(e, t, !0, this.currentSourceOffset, this.zipPlatform, this.encodeFileName);
    if (this.dirRecords.push(r.dirRecord), t) this.push({
        data: hu(e),
        meta: {percent: 100}
    }); else for (this.push({
        data: r.fileRecord,
        meta: {percent: 0}
    }); this.contentBuffer.length;) this.push(this.contentBuffer.shift());
    this.currentFile = null
}, uu.prototype.flush = function () {
    for (var e = this.bytesWritten, t = 0; t < this.dirRecords.length; t++) this.push({
        data: this.dirRecords[t],
        meta: {percent: 100}
    });
    var r = this.bytesWritten - e, n = function (e, t, r, n, i) {
        var s = iu.transformTo("string", i(n));
        return ru.CENTRAL_DIRECTORY_END + "\0\0\0\0" + ou(e, 2) + ou(e, 2) + ou(t, 4) + ou(r, 4) + ou(s.length, 2) + s
    }(this.dirRecords.length, r, e, this.zipComment, this.encodeFileName);
    this.push({data: n, meta: {percent: 100}})
}, uu.prototype.prepareNextSource = function () {
    this.previous = this._sources.shift(), this.openedSource(this.previous.streamInfo), this.isPaused ? this.previous.pause() : this.previous.resume()
}, uu.prototype.registerPrevious = function (e) {
    this._sources.push(e);
    var t = this;
    return e.on("data", (function (e) {
        t.processChunk(e)
    })), e.on("end", (function () {
        t.closedSource(t.previous.streamInfo), t._sources.length ? t.prepareNextSource() : t.end()
    })), e.on("error", (function (e) {
        t.error(e)
    })), this
}, uu.prototype.resume = function () {
    return !!su.prototype.resume.call(this) && (!this.previous && this._sources.length ? (this.prepareNextSource(), !0) : this.previous || this._sources.length || this.generatedError ? void 0 : (this.end(), !0))
}, uu.prototype.error = function (e) {
    var t = this._sources;
    if (!su.prototype.error.call(this, e)) return !1;
    for (var r = 0; r < t.length; r++) try {
        t[r].error(e)
    } catch (e) {
    }
    return !0
}, uu.prototype.lock = function () {
    su.prototype.lock.call(this);
    for (var e = this._sources, t = 0; t < e.length; t++) e[t].lock()
}, nu = uu;
var cu = {
    generateWorker: function (e, t, r) {
        var n = new nu(t.streamFiles, r, t.platform, t.encodeFileName), i = 0;
        try {
            e.forEach((function (e, r) {
                i++;
                var s = function (e, t) {
                        var r = e || t, n = eu[r];
                        if (!n) throw new Error(r + " is not a valid compression method !");
                        return n
                    }(r.options.compression, t.compression), o = r.options.compressionOptions || t.compressionOptions || {},
                    a = r.dir, h = r.date;
                r._compressWorker(s, o).withStreamInfo("file", {
                    name: e,
                    dir: a,
                    date: h,
                    comment: r.comment || "",
                    unixPermissions: r.unixPermissions,
                    dosPermissions: r.dosPermissions
                }).pipe(n)
            })), n.entriesCount = i
        } catch (s) {
            n.error(s)
        }
        return n
    }
}, du = {}, lu = n({}), fu = r({});

function pu(e, t) {
    fu.call(this, "Nodejs stream input adapter for " + e), this._upstreamEnded = !1, this._bindStream(t)
}

lu.inherits(pu, fu), pu.prototype._bindStream = function (e) {
    var t = this;
    this._stream = e, e.pause(), e.on("data", (function (e) {
        t.push({data: e, meta: {percent: 0}})
    })).on("error", (function (e) {
        t.isPaused ? this.generatedError = e : t.error(e)
    })).on("end", (function () {
        t.isPaused ? t._upstreamEnded = !0 : t.end()
    }))
}, pu.prototype.pause = function () {
    return !!fu.prototype.pause.call(this) && (this._stream.pause(), !0)
}, pu.prototype.resume = function () {
    return !!fu.prototype.resume.call(this) && (this._upstreamEnded ? this.end() : this._stream.resume(), !0)
}, du = pu;
var mu = n({}), gu = r({}), _u = s({}), yu = function (e, t, r) {
    var n, i = mu.getTypeOf(t), s = mu.extend(r || {}, Mo);
    s.date = s.date || new Date, null !== s.compression && (s.compression = s.compression.toUpperCase()), "string" == typeof s.unixPermissions && (s.unixPermissions = parseInt(s.unixPermissions, 8)), s.unixPermissions && 16384 & s.unixPermissions && (s.dir = !0), s.dosPermissions && 16 & s.dosPermissions && (s.dir = !0), s.dir && (e = vu(e)), s.createFolders && (n = bu(e)) && wu.call(this, n, !0);
    var o = "string" === i && !1 === s.binary && !1 === s.base64;
    r && void 0 !== r.binary || (s.binary = !o), (t instanceof Yo && 0 === t.uncompressedSize || s.dir || !t || 0 === t.length) && (s.base64 = !1, s.binary = !0, t = "", s.compression = "STORE", i = "string");
    var a;
    a = t instanceof Yo || t instanceof gu ? t : _u.isNode && _u.isStream(t) ? new du(e, t) : mu.prepareContent(e, t, s.binary, s.optimizedBinaryString, s.base64);
    var h = new ia(e, a, s);
    this.files[e] = h
}, bu = function (e) {
    "/" === e.slice(-1) && (e = e.substring(0, e.length - 1));
    var t = e.lastIndexOf("/");
    return t > 0 ? e.substring(0, t) : ""
}, vu = function (e) {
    return "/" !== e.slice(-1) && (e += "/"), e
}, wu = function (e, t) {
    return t = void 0 !== t ? t : Mo.createFolders, e = vu(e), this.files[e] || yu.call(this, e, null, {
        dir: !0,
        createFolders: t
    }), this.files[e]
};

function Eu(e) {
    return "[object RegExp]" === Object.prototype.toString.call(e)
}

var ku = {
    load: function () {
        throw new Error("This method has been removed in JSZip 3.0, please check the upgrade guide.")
    }, forEach: function (e) {
        var t, r, n;
        for (t in this.files) this.files.hasOwnProperty(t) && (n = this.files[t], (r = t.slice(this.root.length, t.length)) && t.slice(0, this.root.length) === this.root && e(r, n))
    }, filter: function (e) {
        var t = [];
        return this.forEach((function (r, n) {
            e(r, n) && t.push(n)
        })), t
    }, file: function (e, t, r) {
        if (1 === arguments.length) {
            if (Eu(e)) {
                var n = e;
                return this.filter((function (e, t) {
                    return !t.dir && n.test(e)
                }))
            }
            var i = this.files[this.root + e];
            return i && !i.dir ? i : null
        }
        return e = this.root + e, yu.call(this, e, t, r), this
    }, folder: function (e) {
        if (!e) return this;
        if (Eu(e)) return this.filter((function (t, r) {
            return r.dir && e.test(t)
        }));
        var t = this.root + e, r = wu.call(this, t), n = this.clone();
        return n.root = r.name, n
    }, remove: function (e) {
        e = this.root + e;
        var t = this.files[e];
        if (t || ("/" !== e.slice(-1) && (e += "/"), t = this.files[e]), t && !t.dir) delete this.files[e]; else for (var r = this.filter((function (t, r) {
            return r.name.slice(0, e.length) === e
        })), n = 0; n < r.length; n++) delete this.files[r[n].name];
        return this
    }, generate: function (e) {
        throw new Error("This method has been removed in JSZip 3.0, please check the upgrade guide.")
    }, generateInternalStream: function (e) {
        var t, r = {};
        try {
            if ((r = mu.extend(e || {}, {
                streamFiles: !1,
                compression: "STORE",
                compressionOptions: null,
                type: "",
                platform: "DOS",
                comment: null,
                mimeType: "application/zip",
                encodeFileName: Eo.utf8encode
            })).type = r.type.toLowerCase(), r.compression = r.compression.toUpperCase(), "binarystring" === r.type && (r.type = "string"), !r.type) throw new Error("No output type specified.");
            mu.checkSupport(r.type), "darwin" !== r.platform && "freebsd" !== r.platform && "linux" !== r.platform && "sunos" !== r.platform || (r.platform = "UNIX"), "win32" === r.platform && (r.platform = "DOS");
            var n = r.comment || this.comment || "";
            t = cu.generateWorker(this, r, n)
        } catch (i) {
            (t = new gu("error")).error(i)
        }
        return new Po(t, r.type || "string", r.mimeType)
    }, generateAsync: function (e, t) {
        return this.generateInternalStream(e).accumulate(t)
    }, generateNodeStream: function (e, t) {
        return (e = e || {}).type || (e.type = "nodebuffer"), this.generateInternalStream(e).toNodejsStream(t)
    }
}, Su = {}, Cu = n({});

function xu(e) {
    this.data = e, this.length = e.length, this.index = 0, this.zero = 0
}

xu.prototype = {
    checkOffset: function (e) {
        this.checkIndex(this.index + e)
    }, checkIndex: function (e) {
        if (this.length < this.zero + e || e < 0) throw new Error("End of data reached (data length = " + this.length + ", asked index = " + e + "). Corrupted zip ?")
    }, setIndex: function (e) {
        this.checkIndex(e), this.index = e
    }, skip: function (e) {
        this.setIndex(this.index + e)
    }, byteAt: function (e) {
    }, readInt: function (e) {
        var t, r = 0;
        for (this.checkOffset(e), t = this.index + e - 1; t >= this.index; t--) r = (r << 8) + this.byteAt(t);
        return this.index += e, r
    }, readString: function (e) {
        return Cu.transformTo("string", this.readData(e))
    }, readData: function (e) {
    }, lastIndexOfSignature: function (e) {
    }, readAndCheckSignature: function (e) {
    }, readDate: function () {
        var e = this.readInt(4);
        return new Date(Date.UTC(1980 + (e >> 25 & 127), (e >> 21 & 15) - 1, e >> 16 & 31, e >> 11 & 31, e >> 5 & 63, (31 & e) << 1))
    }
}, Su = xu;
var Au = {};

function Tu(e) {
    Su.call(this, e);
    for (var t = 0; t < this.data.length; t++) e[t] = 255 & e[t]
}

n({}).inherits(Tu, Su), Tu.prototype.byteAt = function (e) {
    return this.data[this.zero + e]
}, Tu.prototype.lastIndexOfSignature = function (e) {
    for (var t = e.charCodeAt(0), r = e.charCodeAt(1), n = e.charCodeAt(2), i = e.charCodeAt(3), s = this.length - 4; s >= 0; --s) if (this.data[s] === t && this.data[s + 1] === r && this.data[s + 2] === n && this.data[s + 3] === i) return s - this.zero;
    return -1
}, Tu.prototype.readAndCheckSignature = function (e) {
    var t = e.charCodeAt(0), r = e.charCodeAt(1), n = e.charCodeAt(2), i = e.charCodeAt(3), s = this.readData(4);
    return t === s[0] && r === s[1] && n === s[2] && i === s[3]
}, Tu.prototype.readData = function (e) {
    if (this.checkOffset(e), 0 === e) return [];
    var t = this.data.slice(this.zero + this.index, this.zero + this.index + e);
    return this.index += e, t
}, Au = Tu;
var Iu = {};

function Ru(e) {
    Su.call(this, e)
}

n({}).inherits(Ru, Su), Ru.prototype.byteAt = function (e) {
    return this.data.charCodeAt(this.zero + e)
}, Ru.prototype.lastIndexOfSignature = function (e) {
    return this.data.lastIndexOf(e) - this.zero
}, Ru.prototype.readAndCheckSignature = function (e) {
    return e === this.readData(4)
}, Ru.prototype.readData = function (e) {
    this.checkOffset(e);
    var t = this.data.slice(this.zero + this.index, this.zero + this.index + e);
    return this.index += e, t
}, Iu = Ru;
var Bu = {};

function Lu(e) {
    Au.call(this, e)
}

n({}).inherits(Lu, Au), Lu.prototype.readData = function (e) {
    if (this.checkOffset(e), 0 === e) return new Uint8Array(0);
    var t = this.data.subarray(this.zero + this.index, this.zero + this.index + e);
    return this.index += e, t
}, Bu = Lu;
var Ou = {};

function Uu(e) {
    Bu.call(this, e)
}

n({}).inherits(Uu, Bu), Uu.prototype.readData = function (e) {
    this.checkOffset(e);
    var t = this.data.slice(this.zero + this.index, this.zero + this.index + e);
    return this.index += e, t
}, Ou = Uu;
var Pu = n({}), Mu = a({}), Du = function (e) {
    var t = Pu.getTypeOf(e);
    return Pu.checkSupport(t), "string" !== t || Mu.uint8array ? "nodebuffer" === t ? new Ou(e) : Mu.uint8array ? new Bu(Pu.transformTo("uint8array", e)) : new Au(Pu.transformTo("array", e)) : new Iu(e)
}, Nu = {}, ju = n({}), Fu = a({});

function zu(e, t) {
    this.options = e, this.loadOptions = t
}

zu.prototype = {
    isEncrypted: function () {
        return 1 == (1 & this.bitFlag)
    }, useUTF8: function () {
        return 2048 == (2048 & this.bitFlag)
    }, readLocalPart: function (e) {
        var t, r;
        if (e.skip(22), this.fileNameLength = e.readInt(2), r = e.readInt(2), this.fileName = e.readData(this.fileNameLength), e.skip(r), -1 === this.compressedSize || -1 === this.uncompressedSize) throw new Error("Bug or corrupted zip : didn't get enough information from the central directory (compressedSize === -1 || uncompressedSize === -1)");
        if (null === (t = function (e) {
            for (var t in eu) if (eu.hasOwnProperty(t) && eu[t].magic === e) return eu[t];
            return null
        }(this.compressionMethod))) throw new Error("Corrupted zip : compression " + ju.pretty(this.compressionMethod) + " unknown (inner file : " + ju.transformTo("string", this.fileName) + ")");
        this.decompressed = new Yo(this.compressedSize, this.uncompressedSize, this.crc32, t, e.readData(this.compressedSize))
    }, readCentralPart: function (e) {
        this.versionMadeBy = e.readInt(2), e.skip(2), this.bitFlag = e.readInt(2), this.compressionMethod = e.readString(2), this.date = e.readDate(), this.crc32 = e.readInt(4), this.compressedSize = e.readInt(4), this.uncompressedSize = e.readInt(4);
        var t = e.readInt(2);
        if (this.extraFieldsLength = e.readInt(2), this.fileCommentLength = e.readInt(2), this.diskNumberStart = e.readInt(2), this.internalFileAttributes = e.readInt(2), this.externalFileAttributes = e.readInt(4), this.localHeaderOffset = e.readInt(4), this.isEncrypted()) throw new Error("Encrypted zip are not supported");
        e.skip(t), this.readExtraFields(e), this.parseZIP64ExtraField(e), this.fileComment = e.readData(this.fileCommentLength)
    }, processAttributes: function () {
        this.unixPermissions = null, this.dosPermissions = null;
        var e = this.versionMadeBy >> 8;
        this.dir = !!(16 & this.externalFileAttributes), 0 === e && (this.dosPermissions = 63 & this.externalFileAttributes), 3 === e && (this.unixPermissions = this.externalFileAttributes >> 16 & 65535), this.dir || "/" !== this.fileNameStr.slice(-1) || (this.dir = !0)
    }, parseZIP64ExtraField: function (e) {
        if (this.extraFields[1]) {
            var t = Du(this.extraFields[1].value);
            this.uncompressedSize === ju.MAX_VALUE_32BITS && (this.uncompressedSize = t.readInt(8)), this.compressedSize === ju.MAX_VALUE_32BITS && (this.compressedSize = t.readInt(8)), this.localHeaderOffset === ju.MAX_VALUE_32BITS && (this.localHeaderOffset = t.readInt(8)), this.diskNumberStart === ju.MAX_VALUE_32BITS && (this.diskNumberStart = t.readInt(4))
        }
    }, readExtraFields: function (e) {
        var t, r, n, i = e.index + this.extraFieldsLength;
        for (this.extraFields || (this.extraFields = {}); e.index + 4 < i;) t = e.readInt(2), r = e.readInt(2), n = e.readData(r), this.extraFields[t] = {
            id: t,
            length: r,
            value: n
        };
        e.setIndex(i)
    }, handleUTF8: function () {
        var e = Fu.uint8array ? "uint8array" : "array";
        if (this.useUTF8()) this.fileNameStr = Eo.utf8decode(this.fileName), this.fileCommentStr = Eo.utf8decode(this.fileComment); else {
            var t = this.findExtraFieldUnicodePath();
            if (null !== t) this.fileNameStr = t; else {
                var r = ju.transformTo(e, this.fileName);
                this.fileNameStr = this.loadOptions.decodeFileName(r)
            }
            var n = this.findExtraFieldUnicodeComment();
            if (null !== n) this.fileCommentStr = n; else {
                var i = ju.transformTo(e, this.fileComment);
                this.fileCommentStr = this.loadOptions.decodeFileName(i)
            }
        }
    }, findExtraFieldUnicodePath: function () {
        var e = this.extraFields[28789];
        if (e) {
            var t = Du(e.value);
            return 1 !== t.readInt(1) || Wo(this.fileName) !== t.readInt(4) ? null : Eo.utf8decode(t.readData(e.length - 5))
        }
        return null
    }, findExtraFieldUnicodeComment: function () {
        var e = this.extraFields[25461];
        if (e) {
            var t = Du(e.value);
            return 1 !== t.readInt(1) || Wo(this.fileComment) !== t.readInt(4) ? null : Eo.utf8decode(t.readData(e.length - 5))
        }
        return null
    }
}, Nu = zu;
var Hu, Wu = n({}), qu = a({});

function Zu(e) {
    this.files = [], this.loadOptions = e
}

Zu.prototype = {
    checkSignature: function (e) {
        if (!this.reader.readAndCheckSignature(e)) {
            this.reader.index -= 4;
            var t = this.reader.readString(4);
            throw new Error("Corrupted zip or bug: unexpected signature (" + Wu.pretty(t) + ", expected " + Wu.pretty(e) + ")")
        }
    }, isSignature: function (e, t) {
        var r = this.reader.index;
        this.reader.setIndex(e);
        var n = this.reader.readString(4) === t;
        return this.reader.setIndex(r), n
    }, readBlockEndOfCentral: function () {
        this.diskNumber = this.reader.readInt(2), this.diskWithCentralDirStart = this.reader.readInt(2), this.centralDirRecordsOnThisDisk = this.reader.readInt(2), this.centralDirRecords = this.reader.readInt(2), this.centralDirSize = this.reader.readInt(4), this.centralDirOffset = this.reader.readInt(4), this.zipCommentLength = this.reader.readInt(2);
        var e = this.reader.readData(this.zipCommentLength), t = qu.uint8array ? "uint8array" : "array",
            r = Wu.transformTo(t, e);
        this.zipComment = this.loadOptions.decodeFileName(r)
    }, readBlockZip64EndOfCentral: function () {
        this.zip64EndOfCentralSize = this.reader.readInt(8), this.reader.skip(4), this.diskNumber = this.reader.readInt(4), this.diskWithCentralDirStart = this.reader.readInt(4), this.centralDirRecordsOnThisDisk = this.reader.readInt(8), this.centralDirRecords = this.reader.readInt(8), this.centralDirSize = this.reader.readInt(8), this.centralDirOffset = this.reader.readInt(8), this.zip64ExtensibleData = {};
        for (var e, t, r, n = this.zip64EndOfCentralSize - 44; 0 < n;) e = this.reader.readInt(2), t = this.reader.readInt(4), r = this.reader.readData(t), this.zip64ExtensibleData[e] = {
            id: e,
            length: t,
            value: r
        }
    }, readBlockZip64EndOfCentralLocator: function () {
        if (this.diskWithZip64CentralDirStart = this.reader.readInt(4), this.relativeOffsetEndOfZip64CentralDir = this.reader.readInt(8), this.disksCount = this.reader.readInt(4), this.disksCount > 1) throw new Error("Multi-volumes zip are not supported")
    }, readLocalFiles: function () {
        var e, t;
        for (e = 0; e < this.files.length; e++) t = this.files[e], this.reader.setIndex(t.localHeaderOffset), this.checkSignature(ru.LOCAL_FILE_HEADER), t.readLocalPart(this.reader), t.handleUTF8(), t.processAttributes()
    }, readCentralDir: function () {
        var e;
        for (this.reader.setIndex(this.centralDirOffset); this.reader.readAndCheckSignature(ru.CENTRAL_FILE_HEADER);) (e = new Nu({zip64: this.zip64}, this.loadOptions)).readCentralPart(this.reader), this.files.push(e);
        if (this.centralDirRecords !== this.files.length && 0 !== this.centralDirRecords && 0 === this.files.length) throw new Error("Corrupted zip or bug: expected " + this.centralDirRecords + " records in central dir, got " + this.files.length)
    }, readEndOfCentral: function () {
        var e = this.reader.lastIndexOfSignature(ru.CENTRAL_DIRECTORY_END);
        if (e < 0) throw this.isSignature(0, ru.LOCAL_FILE_HEADER) ? new Error("Corrupted zip: can't find end of central directory") : new Error("Can't find end of central directory : is this a zip file ? If it is, see https://stuk.github.io/jszip/documentation/howto/read_zip.html");
        this.reader.setIndex(e);
        var t = e;
        if (this.checkSignature(ru.CENTRAL_DIRECTORY_END), this.readBlockEndOfCentral(), this.diskNumber === Wu.MAX_VALUE_16BITS || this.diskWithCentralDirStart === Wu.MAX_VALUE_16BITS || this.centralDirRecordsOnThisDisk === Wu.MAX_VALUE_16BITS || this.centralDirRecords === Wu.MAX_VALUE_16BITS || this.centralDirSize === Wu.MAX_VALUE_32BITS || this.centralDirOffset === Wu.MAX_VALUE_32BITS) {
            if (this.zip64 = !0, (e = this.reader.lastIndexOfSignature(ru.ZIP64_CENTRAL_DIRECTORY_LOCATOR)) < 0) throw new Error("Corrupted zip: can't find the ZIP64 end of central directory locator");
            if (this.reader.setIndex(e), this.checkSignature(ru.ZIP64_CENTRAL_DIRECTORY_LOCATOR), this.readBlockZip64EndOfCentralLocator(), !this.isSignature(this.relativeOffsetEndOfZip64CentralDir, ru.ZIP64_CENTRAL_DIRECTORY_END) && (this.relativeOffsetEndOfZip64CentralDir = this.reader.lastIndexOfSignature(ru.ZIP64_CENTRAL_DIRECTORY_END), this.relativeOffsetEndOfZip64CentralDir < 0)) throw new Error("Corrupted zip: can't find the ZIP64 end of central directory");
            this.reader.setIndex(this.relativeOffsetEndOfZip64CentralDir), this.checkSignature(ru.ZIP64_CENTRAL_DIRECTORY_END), this.readBlockZip64EndOfCentral()
        }
        var r = this.centralDirOffset + this.centralDirSize;
        this.zip64 && (r += 20, r += 12 + this.zip64EndOfCentralSize);
        var n = t - r;
        if (n > 0) this.isSignature(t, ru.CENTRAL_FILE_HEADER) || (this.reader.zero = n); else if (n < 0) throw new Error("Corrupted zip: missing " + Math.abs(n) + " bytes.")
    }, prepareReader: function (e) {
        this.reader = Du(e)
    }, load: function (e) {
        this.prepareReader(e), this.readEndOfCentral(), this.readCentralDir(), this.readLocalFiles()
    }
}, Hu = Zu;
var Vu = n({}), $u = (Vu = n({}), Hu), Ku = s({});

function Gu(e) {
    return new wo.Promise((function (t, r) {
        var n = e.decompressed.getContentWorker().pipe(new qo);
        n.on("error", (function (e) {
            r(e)
        })).on("end", (function () {
            n.streamInfo.crc32 !== e.decompressed.crc32 ? r(new Error("Corrupted zip : CRC32 mismatch")) : t()
        })).resume()
    }))
}

var Xu = {};

function Yu() {
    if (!(this instanceof Yu)) return new Yu;
    if (arguments.length) throw new Error("The constructor with parameters has been removed in JSZip 3.0, please check the upgrade guide.");
    this.files = {}, this.comment = null, this.root = "", this.clone = function () {
        var e = new Yu;
        for (var t in this) "function" != typeof this[t] && (e[t] = this[t]);
        return e
    }
}

Yu.prototype = ku, Yu.prototype.loadAsync = function (e, t) {
    var r = this;
    return t = Vu.extend(t || {}, {
        base64: !1,
        checkCRC32: !1,
        optimizedBinaryString: !1,
        createFolders: !1,
        decodeFileName: Eo.utf8decode
    }), Ku.isNode && Ku.isStream(e) ? wo.Promise.reject(new Error("JSZip can't accept a stream when loading a zip file.")) : Vu.prepareContent("the loaded zip file", e, !0, t.optimizedBinaryString, t.base64).then((function (e) {
        var r = new $u(t);
        return r.load(e), r
    })).then((function (e) {
        var r = [wo.Promise.resolve(e)], n = e.files;
        if (t.checkCRC32) for (var i = 0; i < n.length; i++) r.push(Gu(n[i]));
        return wo.Promise.all(r)
    })).then((function (e) {
        for (var n = e.shift(), i = n.files, s = 0; s < i.length; s++) {
            var o = i[s];
            r.file(o.fileNameStr, o.decompressed, {
                binary: !0,
                optimizedBinaryString: !0,
                date: o.date,
                dir: o.dir,
                comment: o.fileCommentStr.length ? o.fileCommentStr : null,
                unixPermissions: o.unixPermissions,
                dosPermissions: o.dosPermissions,
                createFolders: t.createFolders
            })
        }
        return n.zipComment.length && (r.comment = n.zipComment), r
    }))
}, Yu.support = a({}), Yu.defaults = Mo, Yu.version = "3.5.0", Yu.loadAsync = function (e, t) {
    return (new Yu).loadAsync(e, t)
}, Yu.external = wo, Xu = Yu;
var Ju = {};
const Qu = Ju.logElem = document.querySelector(".log"), ec = document.querySelector("#logHeading"),
    tc = document.querySelector(".speed");
Ju.log = function (e, t) {
    ec.style.display = "block";
    const r = document.createElement("p");
    return t ? r.innerHTML = e : r.textContent = e, Qu.appendChild(r), r
}, Ju.unsafeLog = function (e) {
    return Ju.log(e, !0)
}, Ju.appendElemToLog = function (e) {
    return ec.style.display = "block", Qu.appendChild(e), Ju.lineBreak(), e
}, Ju.lineBreak = function () {
    Qu.appendChild(document.createElement("br"))
}, Ju.updateSpeed = function (e) {
    tc.innerHTML = e
}, Ju.warning = function (e) {
    return console.error(e.stack || e.message || e), Ju.log(e.message || e)
}, Ju.error = function (e) {
    console.error(e.stack || e.message || e);
    const t = Ju.log(e.message || e);
    return t.style.color = "red", t.style.fontWeight = "bold", t
}, function (e) {
    (function () {
        const t = Xt("instant.io");
        e.WEBTORRENT_ANNOUNCE = Gt.announceList.map((function (e) {
            return e[0]
        })).filter((function (e) {
            return 0 === e.indexOf("wss://") || 0 === e.indexOf("ws://")
        }));
        const r = ["6feb54706f41f459f819c0ae5b560a21ebfead8f"], n = ci((function (e) {
            !function (e) {
                Nr.concat({url: "/__rtcConfig__", timeout: 5e3}, (function (r, n, i) {
                    if (r || 200 !== n.statusCode) e(new Error("Could not get WebRTC config from server. Using default (without TURN).")); else {
                        let n;
                        try {
                            n = JSON.parse(i)
                        } catch (r) {
                            return e(new Error("Got invalid WebRTC config from server: " + i))
                        }
                        delete n.comment, t("got rtc config: %o", n), e(null, n)
                    }
                }))
            }((function (t, r) {
                t && Ju.error(t);
                const n = new to({tracker: {rtcConfig: {...Si.config, ...r}}});
                window.client = n, n.on("warning", Ju.warning), n.on("error", Ju.error), e(null, n)
            }))
        }));

        function i(e) {
            t("got files:"), e.forEach((function (e) {
                t(" - %s (%s bytes)", e.name, e.size)
            })), e.filter(s).forEach(h), function (e) {
                0 !== e.length && (Ju.log("Seeding " + e.length + " files"), n((function (t, r) {
                    if (t) return Ju.error(t);
                    r.seed(e, u)
                })))
            }(e.filter(o))
        }

        function s(e) {
            return ".torrent" === ft.extname(e.name).toLowerCase()
        }

        function o(e) {
            return !s(e)
        }

        function a(e) {
            r.some((function (t) {
                return e.indexOf(t) >= 0
            })) ? Ju.log("File not found " + e) : (Ju.log("Downloading torrent from " + e), n((function (t, r) {
                if (t) return Ju.error(t);
                r.add(e, u)
            })))
        }

        function h(e) {
            Ju.unsafeLog("Downloading torrent from <strong>" + Qt(e.name) + "</strong>"), n((function (t, r) {
                if (t) return Ju.error(t);
                r.add(e, u)
            }))
        }

        function u(e) {
            e.on("warning", Ju.warning), e.on("error", Ju.error);
            const t = document.querySelector("input[name=upload]");
            t.value = t.defaultValue;
            const r = ft.basename(e.name, ft.extname(e.name)) + ".torrent";

            function n() {
                const t = (100 * e.progress).toFixed(1);
                let r;
                r = e.done ? "Done." : (r = e.timeRemaining !== 1 / 0 ? Kn(e.timeRemaining, 0, {includeSeconds: !0}) : "Infinity years")[0].toUpperCase() + r.substring(1) + " remaining.", Ju.updateSpeed("<b>Peers:</b> " + e.numPeers + " <b>Progress:</b> " + t + "% <b>Download speed:</b> " + hi(window.client.downloadSpeed) + "/s <b>Upload speed:</b> " + hi(window.client.uploadSpeed) + "/s <b>ETA:</b> " + r)
            }

            Ju.log('"' + r + '" contains ' + e.files.length + " files:"), e.files.forEach((function (e) {
                Ju.unsafeLog("&nbsp;&nbsp;- " + Qt(e.name) + " (" + Qt(hi(e.length)) + ")")
            })), Ju.log("Torrent info hash: " + e.infoHash), Ju.unsafeLog('<a href="/#' + Qt(e.infoHash) + '" onclick="prompt(\'Share this link with anyone you want to download this torrent:\', this.href);return false;">[Share link]</a> <a href="' + Qt(e.magnetURI) + '" target="_blank">[Magnet URI]</a> <a href="' + Qt(e.torrentFileBlobURL) + '" target="_blank" download="' + Qt(r) + '">[Download .torrent]</a>'), e.on("download", ui(n, 250)), e.on("upload", ui(n, 250)), setInterval(n, 5e3), n(), e.files.forEach((function (e) {
                e.appendTo(Ju.logElem, {maxBlobLength: 2e9}, (function (e, t) {
                    if (e) return Ju.error(e)
                })), e.getBlobURL((function (t, r) {
                    if (t) return Ju.error(t);
                    const n = document.createElement("a");
                    n.target = "_blank", n.download = e.name, n.href = r, n.textContent = "Download " + e.name, Ju.appendElemToLog(n)
                }))
            }));
            const i = document.createElement("a");
            i.href = "#", i.target = "_blank", i.textContent = "Download all files as zip", i.addEventListener("click", (function (t) {
                let r = 0;
                const n = ft.basename(e.name, ft.extname(e.name)) + ".zip";
                let i = new Xu;
                t.preventDefault(), e.files.forEach((function (t) {
                    t.getBlob((function (s, o) {
                        if (r += 1, s) return Ju.error(s);
                        i.file(t.path, o), r === e.files.length && (e.files.length > 1 && (i = i.folder(e.name)), i.generateAsync({type: "blob"}).then((function (e) {
                            const t = URL.createObjectURL(e), r = document.createElement("a");
                            r.download = n, r.href = t, r.click(), setTimeout((function () {
                                URL.revokeObjectURL(t)
                            }), 3e4)
                        }), Ju.error))
                    }))
                }))
            })), Ju.appendElemToLog(i)
        }

        !function () {
            to.WEBRTC_SUPPORT || Ju.error("This browser is unsupported. Please use a browser with WebRTC support."), n((function () {
            }));
            const e = document.querySelector("input[name=upload]");
            var t, r, s;
            e && ("function" == typeof (r = function (e, t) {
                if (e) return Ju.error(e);
                i(t = t.map((function (e) {
                    return e.file
                })))
            }) && (s = r, r = {}), "string" == typeof r && (r = {type: r}), (t = e).addEventListener("change", (function (e) {
                if (0 === t.files.length) return s(null, []);
                var n = new FileReader, i = 0, o = [];

                function a(e) {
                    var i = t.files[e];
                    "text" === r.type ? n.readAsText(i) : "url" === r.type ? n.readAsDataURL(i) : n.readAsArrayBuffer(i)
                }

                n.addEventListener("load", (function (e) {
                    o.push({file: t.files[i], target: e.target}), ++i === t.files.length ? s(null, o) : a(i)
                })), a(i)
            }))), Yt("body", i);
            const o = document.querySelector("form");

            function h() {
                const e = decodeURIComponent(window.location.hash.substring(1)).trim();
                "" !== e && a(e)
            }

            o && o.addEventListener("submit", (function (e) {
                e.preventDefault(), a(document.querySelector("form input[name=torrentId]").value.trim())
            })), h(), window.addEventListener("hashchange", h), "registerProtocolHandler" in navigator && navigator.registerProtocolHandler("magnet", window.location.origin + "#%s", "Instant.io")
        }()
    }).call(this)
}.call(this, "undefined" != typeof global ? global : "undefined" != typeof self ? self : "undefined" != typeof window ? window : {})
}
();