/*jslint nomen: true */
/* eslint-disable indent */
/* eslint-disable no-param-reassign */
/*global poloAfrica: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}

if (typeof Function.prototype.wrap === "undefined") {

/*
  Function.prototype.wrap = function (wrapper, ..._vs) {
    let _method = this; //the function
    return function (...vs) {
      return wrapper.apply(this, [_method.bind(this), ..._vs, ...vs]);
    };
  };
*/
  Function.prototype.wrap  = function (wrapper, ...first) {
    var method = this;
    return function(...next) {
      let last = [...first, ...next];
      if (wrapper) {
        return wrapper(method.bind(this), ...last);
      }
    }
  }
}

window.requestAnimationFrame =
  window.requestAnimationFrame ||
  window.mozRequestAnimationFrame ||
  window.webkitRequestAnimationFrame ||
  window.msRequestAnimationFrame ||
  function (f) {
    "use strict";
    //return window.setTimeout(f, (1000 / 60));
    return window.setTimeout(f, 16.666);
  }; // simulate calling code 60
window.cancelAnimationFrame =
  window.cancelAnimationFrame ||
  window.mozCancelAnimationFrame ||
  window.webkitCancelAnimationFrame ||
  window.msCancelAnimationFrame ||
  function (requestID) {
    "use strict";
    window.clearTimeout(requestID);
  }; //fall back

poloAfrica.meta = (function () {
  "use strict";

  const supportsES6 = (function () {
    try {
      new Function("(a = 0) => a");
      //  alert('ohgood')
      return true;
    } catch (err) {
      //  alert('ohno')
      return false;
    }
  })();

  function existy(x) {
    return x != null;
  }

  function fnull(fun, ...defaults) {
    return function (...defaults) {
      var myargs = meta.toArray(defaults),
        args = myargs.map((e, i) => (existy(e) ? e : defaults[i]));
      return fun(...args);
    };
  }

  function pApply(fn, ...cache) {
    return (...args) => {
      const all = cache.concat(args);
      return all.length >= fn.length ? fn(...all) : pApply(fn, ...all);
    };
  }

  function pApply2(fn, ...cache) {
    return (...args) => {
      const all = cache.concat(args),
        reached = all.length >= fn.length;
      return reached ? () => fn(...all) : pApply(fn, ...all);
    };
  }

  function reverseArray(array) {
    var i,
      L = array.length,
      old;
    //FRWL, YOLT, OHMSS, LALD
    array = Array.from(array); //slice?
    for (i = 0; i < Math.floor(L / 2); i += 1) {
      old = array[i];
      //1:FRWL / LALD
      //2: YOLT / OHMSS
      array[i] = array[L - 1 - i];
      array[L - 1 - i] = old;
    }
    return array;
  }

  function shout(m) {
    var applier = function (f, ...args) {
      return function (...newargs) {
        return f(...args, ...newargs);
      };
    };
    return applier(window[m].bind(window));
  }

  function doBestDefer(coll, pred, arg) {
    let group = coll, //could be a group of primitives, objects, functions...
      domap = (fn, ag) => (isFunction(fn) ? curryDefer(fn)(ag) : fn),
      func = pred;
    if (typeof arg != "undefined") {
      if (isArray(arg)) {
        //map args to specific functions
        if (arg[1] && isArray(arg[1])) {
          group = coll.map((item, i) => domap(item, arg[i]));
        } else {
          //arg[1] for group
          //could be one arg for group and predicate, or separate, arg[1] conditionally exists
          group = coll.map((item) => domap(item, arg[1] || arg[0]));
        }
        //arg[0] for predicate
        func = curryDefer(pred)(arg[0]);
      } else if (isFunction(arg)) {
        //if function assumes arg is for group only
        group = coll.map((item) => domap(item, getResult(arg)));
      } else {
        //assumes arg is for predicate only
        group = coll;
        func = curryDefer(pred)(arg);
      }
    }
    return group.reduce((champ, contender) =>
      func(champ, contender) ? champ : contender
    );
  }

  function doBestInvoke(coll, pred, arg) {
    let group = coll,
      domap = (fn, ag) => (isFunction(fn) ? curry(fn)(ag) : fn),
      func = pred;
    if (typeof arg != "undefined") {
      /*problemo the idea here is to divert the arg to the pred/action or both by wrapping them in
      an array OR a function or nothing BUT what if the arg is already a function or an array before the wrap
      it would be BEST to tailor this to individual use cases
      */
      if (isArray(arg)) {
        if (arg[1] && isArray(arg[1])) {
          group = coll.map((item, i) => domap(item, arg[i]));
        } else {
          group = coll.map((item) => domap(item, arg[1] || arg[0]));
        }
        func = curry(pred)(arg[0]);
      } else if (isFunction(arg)) {
        group = coll.map((item) => domap(item, getResult(arg)));
      } else {
        group = coll;
        func = curry(pred)(arg);
      }
    }
    return group.reduce((champ, contender) =>
      func(champ, contender) ? champ : contender
    );
  }

  /*
    function composeVerbose (...fns) {
      return fns.reduce((f, g) => {
        return (...vs) => {
        //console.log(f, g, ...vs);
          return f(g(...vs));
        };
      });
    }
  */

  const def = (x) => typeof x !== "undefined",
    tagTester = (name) => {
      const tag = "[object " + name + "]";
      return function (obj) {
        return toString.call(obj) === tag;
      };
    },
    isBoolean = tagTester("Boolean"),
    isFunction = tagTester("Function"),
    isString = tagTester("String"),
    isArray = tagTester("Array"),
    getResult = (o) => (isFunction(o) ? o() : o),
    byId = (str) => document.getElementById(str),
    byIdDefer = (str) => () => byId(str),
    byTag = (str, flag = false) => {
      const m = flag ? "querySelectorAll" : "querySelector";
      return document[m](str);
    },
    byTagScope =
      (context) =>
      (str, flag = false) => {
        const m = flag ? "querySelectorAll" : "querySelector";
        return context[m](str);
      },
    curryDefer = (fun) => (a) => () => fun(a),
    curry = (fun) => (a) => fun(a),
    doPartial = (flag) => {
      return function p(f, ...args) {
        if (f.length === args.length) {
          return flag ? () => f(...args) : f(...args);
        }
        return (...rest) => p(f, ...args, ...rest);
      };
    },
    compose = (...fns) =>
      fns.reduce(
        (f, g) =>
          (...vs) =>
            f(g(...vs))
      ),
    doWhenFactory = (n) => {
      const both = (pred, action, v) => {
          if (pred(v)) {
            return action(v);
          }
        },
        act = (pred, action, v) => {
          if (getResult(pred)) {
            return action(v);
          }
        },
        predi = (pred, action, v) => {
          if (pred(v)) {
            return getResult(action);
          }
        },
        none = (pred, action) => {
          if (getResult(pred)) {
            return getResult(action);
          }
        },
        all = [none, predi, act, both];
      return all[n] || none;
    },
    //for signatures resistent to straightforward partial application or currying
    //largely assumes we need to return an element for further processing
    mittelFactory = (arg) => {
      let res;
      if (arg && isBoolean(arg)) {
        return (f, o, v = undefined) =>
          //dynamic method (add/remove etc)
          (m) => {
            res = f(o, m, v);
            return res || o;
          };
      } else if (!arg && isBoolean(arg)) {
        return (f, m, v = undefined) =>
          //optional key/value; dynamic key
          (o, k) => {
            res = def(v) ? f(o, m, k, v) : f(o, m, v);
            return res || o;
          };
      }
      //typical use park STATIC values
      return (f, m, k = undefined) => {
        //optional key/value;dynamic value
        return (o, v) => {
          //optional callback;typically get sub property, or getResult
          if (isFunction(arg)) {
            res = def(k) ? f(arg(o), m, k, v) : f(arg(o), m, v);
            return res || o;
          }
          res = def(k) ? f(o, m, k, v) : f(o, m, v);
          return res || o;
        };
      };
    },
    curryRight = (i, defer = false) => {
      const once = {
          imm: (fn) => (a) => fn(a),
          def: (fn) => (a) => () => fn(a),
        },
        twice = {
          imm: (fn) => (b) => (a) => {
            // console.log(fn,a,b)
            return fn(a, b);
          },
          def: (fn) => (b) => (a) => () => fn(a, b),
        },
        thrice = {
          imm: (fn) => (c) => (b) => (a) => fn(a, b, c),
          def: (fn) => (c) => (b) => (a) => () => fn(a, b, c),
        },
        quart = {
          imm: (fn) => (d) => (c) => (b) => (a) => fn(a, b, c, d),
          def: (fn) => (d) => (c) => (b) => (a) => () => fn(a, b, c, d),
        },
        options = [null, once, twice, thrice, quart],
        ret = options[i],
        noOp = () => {
          return false;
        };
      return ret && defer ? ret.def : ret ? ret.imm : noOp;
    },
    curryLeft = (i, defer = false) => {
      const once = {
          imm: (fn) => (a) => fn(a),
          def: (fn) => (a) => () => fn(a),
        },
        twice = {
          imm: (fn) => (a) => (b) => fn(a, b),
          def: (fn) => (a) => (b) => () => fn(a, b),
        },
        thrice = {
          imm: (fn) => (a) => (b) => (c) => fn(a, b, c),
          def: (fn) => (a) => (b) => (c) => () => fn(a, b, c),
        },
        quart = {
          imm: (fn) => (a) => (b) => (c) => (d) => fn(a, b, c, d),
          def: (fn) => (a) => (b) => (c) => (d) => () => fn(a, b, c, d),
        },
        options = [null, once, twice, thrice, quart],
        ret = options[i],
        noOp = () => {
          return false;
        };
      return ret && defer ? ret.def : ret ? ret.imm : noOp;
    },
    toArray = (coll, cb = () => true) => {
      let i = 0,
        arr,
        grp = [];
      if (isArray(coll)) {
        while (coll[i]) {
          arr = Array.prototype.slice.call(coll[i]).filter(cb);
          grp = grp.concat(arr);
          i++;
        }
        return grp;
      }
      if (coll) {
        return Array.prototype.slice.call(coll).filter(cb);
      }
      return [];
    },
    best = (fun, coll) => {
      return coll.reduce((champ, contender) =>
        fun(champ, contender) ? champ : contender
      );
    },
    bestLog = (fun, coll) => {
      return coll.reduce((champ, contender) => {
        let res = fun(champ, contender);
        console.log('log', res, champ);
        return res ? champ : contender;
      });
    },
    //can't assign i to another variable
    alternate = (i, n) => () => (i += 1) % n,
    doAlternate = (j = 2) => {
      const f = alternate(0, j);
      return (actions, predicate = true) => {
        let [uno, duo] = getResult(predicate) ? actions : actions.reverse();
        //a more sophisticated version would examine type of arg and apply to actions/predicate accordingly
        return (arg) => {
          if (arg) {
            return best(f, [pApply(uno, arg), pApply(duo, arg)])();
          }
          return best(f, [uno, duo])();
        };
      };
    },
    invokeMethod = (o, m, v) => {
      // console.log(o,m,v);
      try {
        return getResult(o)[m](v);
      } catch (e) {
        return getResult(o)[m](getResult(v));
      }
    },
    invokePropertyMethod = (o, p, m, k, v) => {
      return getResult(o)[p][m](k, v);
    },
    invoke = (f, v) => f(getResult(v)),
    invokePair = (o, m, k, v) => {
      return getResult(o)[m](k, v);
    },
    soInvoke = (o, m, ...rest) => o[m](...rest),
    invokeEach = (m, funs) => {
      return (o) => {
        if (funs) {
          let obj = getResult(o);
          funs[m]((f) => f(obj));
          return obj;
        }
        return o;
      };
    };
  return {
    $: byId,
    $$: byIdDefer,
    $Q: byTag,
    $$Q:
      (str, flag = false) =>
      () =>
        byTag(str, flag),
    byTagScope: byTagScope,
    compose: compose,
    getResult: getResult,
    tagTester: tagTester,
    doWhenFactory: doWhenFactory,
    doBest: doBestDefer,
    doBestInvoke: doBestInvoke,
    prepBestArgs: function (type, arg) {
      if (isArray(type)) {
        return [arg];
      } else if (isFunction(type)) {
        return always(arg);
      }
      return arg;
    },
    doPartial: doPartial,
    doOnce: (i) => (cb) => {
      if (i) {
        cb();
        i -= 1;
      }
    },
    setter: (o, k, v) => {
      // console.log(o,k,v)
      let obj = getResult(o);
      obj[k] = v;
    },
    setterBridge:
      (pre = getResult, post = getResult) =>
      (o, k, v) => {
        // console.log(o,k,v)
        let obj = pre(o);
        obj[k] = v;
        return post(obj);
      },
    pApply: pApply,
    pass: (ptl, o) => {
      ptl(getResult(o));
      return o;
    },
    always: (a) => () => a,
    alwaysLog: (a) => () => {
      console.log(a);
      return a;
    },
    defer:
      (flag) =>
      (fn, ...cache) => {
        return (...args) => {
          const all = cache.concat(args),
            pass = all.length >= fn.length;
          if (pass && !flag) {
            return fn(...all);
          } else if (pass && flag) {
            return () => fn(...all);
          }
          return pApply(fn, ...all);
        };
      },
    identity: (a) => a,
    isFunction: isFunction,
    isBoolean: isBoolean,
    curryRight: curryRight,
    curryLeft: curryLeft,
    mittelFactory: mittelFactory,
    invoke: invoke,
    invoker: curryRight(2)(invoke),
    invokeMethod: invokeMethod,
    invokeMethodBind: (o, m, v) => {
      return getResult(o)[m].call(o, v);
    },
    invokeMethodV: (o, p, m, v) => {
      return getResult(o)[p][v](m);
    },
    invokePropertyMethod: invokePropertyMethod,
    invokePair: invokePair,
    invokeEach: invokeEach,
    isArray: isArray,
    lazyVal: (m, p, o, v) => {
      return getResult(o)[m](p, v);
    },
    ///invokeMethodBridge: (m, v, o) => invokeMethod(o, m, v),
    invokeMethodBridge: (m, v, o) => {
      return isArray(v) ? invokePair(o, m, v[0], v[1]) : invokeMethod(o, m, v);
    },
    invokeMethodBridgeCB: (cb) => (m, v, o) => {
      //console.log(cb(o), m, v);
      return invokeMethod(cb(o), m, v);
    },
    invokeClass: (o, s, m, v) => getResult(o)[s][m](v),
    isString: isString,
    negate: (f, ...args) => !f(...args),
    defernegate:
      (f, ...args) =>
      () =>
        !f(...args),
    negator:
      (f, ...args) =>
      (...rest) =>
        !f(...args, ...rest),
    zip: (m, funs, vals) => vals[m]((v, i) => funs[i](v)),
    eitherOr: (a, b, pred) => (pred ? a : b),
    compare: (pred) => (p, a, b) => {
      return typeof p === "string"
        ? //compare common Property of two objects
          pred(a[p], b[p])
        : p
        ? //compare two Properties of one object
          pred(p[a], p[b])
        : pred(a, b);
    },
    toArray: toArray,
    doAlternate: doAlternate,
    driller: (o, p) => o[p] || o,
    getter: (o, p) => {
      return getResult(o)[p];
    },
    getTgt: (str) => byIdDefer(str),
    soInvoke: soInvoke,
    best: best,
    getLast: (o, i = 1) => {
      if (isArray(o)) {
        return o[o.length - i];
      }
    },
    reverse: reverseArray,
    deferCB: pApply2,
    doTest: function (x, ...args) {
      console.log(x, ...args);
      return x;
    },
    supportsES6: supportsES6,
    shout: shout,
  };
})();
