/*jslint nomen: true */
/* eslint-disable indent */
/*global poloAfrica: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}

(function (window) {
  "use strict";

  //PARK THIS HERE WITH COMMENTS BUT DEPLOY ON THE PAGES THAT REQUIRE IT
  //https://webdesign.tutsplus.com/tutorials/javascript-debounce-and-throttle--cms-36783
  //initialize throttlePause variable outside throttle function
  function throttle(callback, time) {
    //don't run the function if throttlePause is true
    if (throttlePause) {
      return;
    }
    //set throttlePause to true after the if condition. This allows the function to be run once
    throttlePause = true;
    //setTimeout runs the callback within the specified time
    setTimeout(() => {
      callback();
      //throttlePause is set to false once the function has been called, allowing the throttle function to loop
      throttlePause = false;
    }, time);
  }

  let throttlePause,
    lastTime = 0,
    prefixes = "webkit moz ms o".split(" "),
    requestAnimationFrame = window.requestAnimationFrame, //get unprefixed rAF and cAF, if present
    cancelAnimationFrame = window.cancelAnimationFrame,
    prefix,
    i;
  // loop through vendor prefixes and get prefixed rAF and cAF
  for (i = 0; i < prefixes.length; i++) {
    if (requestAnimationFrame && cancelAnimationFrame) {
      break;
    }
    prefix = prefixes[i];
    requestAnimationFrame =
      requestAnimationFrame || window[prefix + "RequestAnimationFrame"];
    cancelAnimationFrame =
      cancelAnimationFrame ||
      window[prefix + "CancelAnimationFrame"] ||
      window[prefix + "CancelRequestAnimationFrame"];
  }

  // fallback to setTimeout and clearTimeout if either request/cancel is not supported
  if (!requestAnimationFrame || !cancelAnimationFrame) {
    requestAnimationFrame = function (callback, element) {
      let currTime = new Date().getTime(),
        timeToCall = Math.max(0, 16 - (currTime - lastTime)),
        id = window.setTimeout(function () {
          callback(currTime + timeToCall);
        }, timeToCall);
      lastTime = currTime + timeToCall;
      return id;
    };

    cancelAnimationFrame = function (id) {
      window.clearTimeout(id);
    };
  }
  // put in global namespace
  window.requestAnimationFrame = requestAnimationFrame;
  window.cancelAnimationFrame = cancelAnimationFrame;
})(window);

if (typeof Function.prototype.method === "undefined") {
  Function.prototype.method = function (name, func) {
    "use strict";
    this.prototype[name] = func;
    return this;
  };
}

function processResponse(canvas, request, container = ".wrap", permit = false) {
  //canvas is ideally body which should have no attributes that require changing
  //that should happen on a wrapping element <body><div class="wrap"><head/><main/></footer><div></body>
  //this new container gets appended to the old body
  //but we need to remove/add scripts so they can interact with the new DOM
  var frag = document.createDocumentFragment(),
    dummy = document.createElement("div"),
    el,
    mycanvas,
    pass = false,
    park = false,
    scripts = [],
    append = (node) => (src) => {
      let script = document.createElement("script"),
        source = src.getAttribute("src");
      script.setAttribute("src", source);
      node.appendChild(script);
    },
    cb = () => {};
  dummy.innerHTML = request.responseText;

  while ((el = dummy.firstChild)) {
    pass = pass || el.nodeName === "DIV";
    park = el.nodeName === "SCRIPT";
    if (el.nodeType !== 1 || (permit && park && pass)) {
      let elem = el.parentNode.removeChild(el);
      if (park && pass) {
        scripts.push(elem);
      }
    } else {
      frag.appendChild(el);
    }
  }
  mycanvas = frag.querySelector(container);
  if (mycanvas) {
    canvas.appendChild(mycanvas);
    cb = append(canvas);
    scripts.forEach(cb);
  }
}

function fromPost(form) {
  var i,
    query = "",
    multi = "",
    type = "",
    myfile = null,
    myfilename = "",
    fail = false;
  for (i = 0; i < form.elements.length; i += 1) {
    fail = false;
    type = form.elements[i].type;
    //need to validate if a radio/checkbox button is checked before including it in query
    multi = type === "radio" || type === "checkbox" ? true : false;
    if (multi && !form.elements[i].checked) {
      fail = true;
    }
    fail =
      fail || (form.elements[i].name === "cancel" && form.elements[i].value);
    //exclude submit if no name. WATCH OUT
    if (!fail && form.elements[i].name) {
      if (type === "file") {
        myfilename = form.elements[i].name;
        myfile = form.elements[i].files[0];
      } else {
        query += form.elements[i].name;
        query += "=";
        query += encodeURI(form.elements[i].value);
      }
      query += "&";
    }
  }
  return [query, myfilename, myfile];
}

function payment(total, rate, pay, fixed = 0) {
  var count = 0;
  while (total > 0) {
    //interest
    total *= rate;
    //monthly payment
    total -= pay;
    //fixed charges (before or after rate applied?)
    total += fixed;
    //duration
    count++;
  }
  return [total, count];
}

//https://codepen.io/kallil-belmonte/pen/KKKRoyx
// CHECK IF IMAGE EXISTS
function checkIfImageExists(url, callback) {
  const img = new Image();
  img.src = url;

  if (img.complete) {
    callback(true);
  } else {
    img.onload = () => {
      callback(true);
    };

    img.onerror = () => {
      callback(false);
    };
  }
}

/* USAGE
checkIfImageExists('http://website/images/img.png', (exists) => {
  if (exists) {
    console.log('Image exists. ')
  } else {
    console.error('Image does not exists.')
  }
});
*/
function getNextElement(node, type = 1) {
  if (node && node.nodeType === type) {
    return node;
  }
  if (node && node.nextSibling) {
    return getNextElement(node.nextSibling);
  }
  return null;
}

function getPrevElement(node, type = 1) {
  if (node && node.nodeType === type) {
    return node;
  }
  if (node && node.previousSibling) {
    return getPrevElement(node.previousSibling);
  }
  return null;
}

function getDir(node, dir) {
  if (dir.match(/^first/)) {
    return node.firstElementChild ? "firstElementChild" : dir;
  }

  if (dir.match(/^last/)) {
    return node.lastElementChild ? "lastElementChild" : dir;
  }
}

function nodeCheck(node) {
  node = poloAfrica.meta.getResult(node);
  if (node && node.nodeType === 1) {
    return node;
  }
  return null;
}

function getTargetNode2(node, reg, dir = "firstChild") {
  if (!node) {
    return null;
  }
  dir = getDir(node, dir);
  let res,
    mynode = node.nodeType === 1 ? node : getNextElement(node);
  mynode = mynode || getPrevElement(node);
  res = mynode && mynode.nodeName.match(reg);
  if (!res) {
    let testnode = mynode && getNextElement(mynode[dir]);
    mynode = testnode || getPrevElement(mynode[dir]); //dir MAY be lastChild
    return mynode && getTargetNode(mynode, reg, dir);
  }
  return mynode;
}

function getTargetNode(node, reg, dir = "firstChild") {
  if (!node) {
    return null;
  }
  //dir = getDir(node, dir);
  let res,
    mynode = node.nodeType === 1 ? node : getNextElement(node);
  res = mynode && mynode.nodeName.match(reg);
  if (!res) {
    let testnode = mynode && getNextElement(mynode[dir]);
    mynode = testnode || getPrevElement(mynode[dir]); //dir MAY be lastChild
    return mynode && getTargetNode(mynode, reg, dir);
  }
  return mynode;
}

function findOrCreate(ancrnode, nodestr, idstr) {
  let node = comp(utils.setId(id).wrap(meta.pass), utils.doMake)(nodestr);
}

function applyClass(kls, el, flag = false) {
  var meta = poloAfrica.meta,
    alt = meta.doAlternate(),
    invoke = (f) => f(),
    def = (x) => typeof x !== "undefined",
    isFunction = meta.tagTester("Function"),
    getRes = function (arg) {
      if (isFunction(arg)) {
        return arg();
      }
      return arg;
    },
    curry22 = meta.curryRight(2, true),
    pApply = meta.pApply,
    best = (coll, fun) => () => coll.reduce((a, b) => (fun(a, b) ? a : b)),
    bestLate = (coll) => (fun) => coll.reduce((a, b) => (fun ? a : b)),
    displayClass = meta.pApply(meta.invokeMethod, getRes(el).classList),
    exec = displayClass("add"),
    undo = displayClass("remove"),
    enter = curry22(meta.invoke)(kls)(exec),
    exit = curry22(meta.invoke)(kls)(undo),
    noOp = () => undefined,
    state = flag ? exit : enter,
    query = meta.compose(bestLate([enter, exit])),
    defer = best([noOp, state], meta.$$Q("." + kls));
  return {
    exec: enter,
    undo: exit,
    apply: meta.compose(invoke, defer),
    query: meta.compose(invoke, query),
    toggle: alt([enter, exit]),
  };
}

poloAfrica.utils = (function () {
  const meta = poloAfrica.meta,
    tagTester = meta.tagTester,
    isFunction = tagTester("Function"),
    getRes = function (arg) {
      if (isFunction(arg)) {
        return arg();
      }
      return arg;
    },
    invokeMethod = meta.invokeMethod,
    invokeMethodBridge = meta.invokeMethodBridge,
    invokeMethodBridgeCB = meta.invokeMethodBridgeCB,
    ptL = meta.doPartial(),
    compose = meta.compose,
    pass = meta.pass,
    getter = (o, p) => o && getRes(o)[p],
    curry2 = meta.curryRight(2),
    curry22 = meta.curryRight(2, true),
    curry3 = meta.curryRight(3),
    curryL3 = meta.curryLeft(3),
    curryL33 = meta.curryLeft(3, true),
    getTarget = curry2(getter)("target"),
    getParent = curry2(getter)("parentNode"),
    getClassList = curry2(getter)("classList"),
    doTextNow = ptL(invokeMethod, document, "createTextNode"),
    setAttribute = ptL(meta.lazyVal, "setAttribute"),
    removeAttribute = ptL(meta.invokeMethodBridge, "setAttribute"),
    setLink = curry2(setAttribute("href")),
    setDisabled = curry2(setAttribute("disabled")),
    getImgSrc = curryL3(invokeMethodBridge)("getAttribute")("src"),
    addKlas = ptL(invokeMethodBridge, "add"),
    remKlas = ptL(invokeMethodBridge, "remove"),
    undoActive = compose(remKlas("active"), getClassList).wrap(pass),
    undoInviz = compose(remKlas("invisible"), getClassList).wrap(pass),
    doInviz = compose(addKlas("invisible"), getClassList).wrap(pass),
    doEach = curryL3(invokeMethodBridgeCB(getRes))("forEach"),
    getZero = curry2(getter)("0"),
    getLength = curry2(getter)("length"),
    getKey = compose(getZero, curryL3(invokeMethod)(window.Object)("keys")),
    modulo = (n, i) => i % n,
    increment = (i) => i + 1,
    doInc = (n) => compose(ptL(modulo, n), increment),
    append = ptL(invokeMethodBridgeCB(getRes), "appendChild"),
    prepAttrs = (keys, vals) => curryL33(meta.zip)("map")(keys)(vals),
    removeElement = (node) => {
      return (
        node && node.parentNode && node.parentNode.removeChild(getRes(node))
      );
    },
    getStyle = curry2(meta.getter)("style"),
    removeStyle = curry3(meta.invokeMethod)("style")("removeAttribute"),
    removeDisabled = curry3(meta.invokeMethod)("disabled")("removeAttribute"),
    setProperty = meta.pApply(
      meta.mittelFactory(getStyle),
      meta.invokePair,
      "setProperty"
    ),
    prep2Append = (doEl, doAttrs) =>
      compose(
        append,
        curry2(meta.invoke)(doEl),
        ptL(meta.invokeEach, "forEach"),
        doAttrs
      )();

  return {
    applyClass: applyClass,
    addLoadEvent: (func) => {
      var oldonload = window.onload;
      if (typeof window.onload != "function") {
        window.onload = func;
      } else {
        window.onload = function () {
          oldonload();
          func();
        };
      }
    },
    removeElement: removeElement,
    removeChildNodes: (node) => {
      while (node.hasChildNodes()) {
        node.removeChild(node.firstChild);
      }
    },
    prep2Append: prep2Append,
    prepAttrs: prepAttrs,
    getNextElement: getNextElement,
    getPrevElement: getPrevElement,
    getTargetNode: getTargetNode,
    getElementHeight: (el) => {
      return el.getBoundingClientRect().height || el.offsetHeight;
    },
    getNaturalDims: (DOMelement) => {
      var img = new Image();
      img.src = DOMelement.src;
      return { width: img.width, height: img.height };
    },
    getTarget: getTarget,
    removeStyle: removeStyle,
    setProperty: setProperty,
    getRes: getRes,
    getParent: getParent,
    getParent2: compose(getParent, getParent),
    getText: curry2(getter)("innerHTML"),
    doMakeDefer: curryL33(invokeMethod)(document)("createElement"),
    doMake: curryL3(invokeMethod)(document)("createElement"),
    //doText: deferPTL(invokeMethod, document, "createTextNode"),
    doText: curryL33(invokeMethod)(document)("createTextNode"),
    doTextCBNow: curryL3(invokeMethod)(document)("createTextNode"),
    prepend: curry2(ptL(invokeMethodBridgeCB(getRes), "appendChild")),
    append: append,
    appendAlt: ptL(meta.mittelFactory()(invokeMethod, "appendChild")),
    appendCB: curryL3(invokeMethodBridgeCB(getRes))("appendChild"),
    getAttrs: curryL3(invokeMethodBridge)("getAttribute"),
    matchLink: compose(
      curry3(invokeMethod)(/^a$/i)("match"),
      curry2(getter)("nodeName"),
      getTarget
    ),
    matchPath: compose(
      curry3(invokeMethod)(/jpe?g/i)("match"),
      curryL3(invokeMethodBridge)("getAttribute")("href")
    ),
    getImgPath: compose(getImgSrc, getTarget),
    setId: curry2(setAttribute("id")),
    setKlas: curry2(setAttribute("class")),
    setKlasLazy: setAttribute("class"),
    setSrc: curry2(setAttribute("src")),
    setAlt: curry2(setAttribute("alt")),
    setVal: curry2(setAttribute("value")),
    setMin: curry2(setAttribute("min")),
    setMax: curry2(setAttribute("max")),
    setType: curry2(setAttribute("type")),
    setTitle: curry2(setAttribute("title")),
    setHyper: curry2(setAttribute("href")),
    setDisabled: setDisabled("disabled"),
    unsetDisabled: removeDisabled,
    addKlas: addKlas,
    remKlas: remKlas,
    getClassList: getClassList,
    applyClassList: (partial, o) => {
      partial(o.classList);
      //console.log(o, 7);
      return o;
    },
    setInnerHTML: meta.mittelFactory()(meta.setter, "innerHTML"),
    clearInnerHTML: curry3(meta.setter)("")("innerHTML"),
    setNavId: curry2(setAttribute("id"))("navigation").wrap(pass),
    setHref: setLink(".").wrap(pass),
    doActive: compose(addKlas("active"), getClassList).wrap(pass),
    undoActive: undoActive,
    undoActiveCB: doEach(undoActive),
    getKeys: compose(doTextNow, getKey),
    doTextNow: doTextNow,
    getLast: (array) => array[array.length - 1],
    getZero: getZero,
    getZeroCB: curry22(getter)("0"),
    incrementer: compose(doInc, getLength),
    applyPortrait: curry3((m, o, v) => o.classList[m](v))("portrait"),
    insertNeu: (el, after) => {
      let p = el.parentNode,
        get = getNextElement,
        first = get(p.firstChild),
        node = after ? get(first.nextSibling) : first;
      return p.insertBefore(el, node);
    },
    insertAfter: (newElement, targetElement) => {
      var parent = targetElement.parentNode;
      if (parent.lastChild === targetElement) {
        parent.appendChild(newElement);
      } else if (newElement) {
        parent.insertBefore(
          newElement,
          getNextElement(targetElement.nextSibling)
        );
      }
    },
    displayLoading: function (element) {
      var doLink = utils.doMakeDefer("a"),
        doImg = utils.doMakeDefer("img"),
        setLink = prep2Append(
          doLink,
          utils.prepAttrs([utils.setId], ["progress"])
        ),
        keys = [utils.setAlt, utils.setSrc, utils.setKlas],
        values = ["loading...", "assets/progressbar.gif", "loading"],
        setImg = prep2Append(doImg, utils.prepAttrs(keys, values));
      return compose(setImg, setLink)(element);
    },
    fadeUp: function (element, red, green, blue) {
      let doFade = (col) => col + Math.ceil((255 - col) / 10);
      if (element.fade) {
        clearTimeout(element.fade);
      }
      element.style.backgroundColor =
        "rgb(" + red + "," + green + "," + blue + ")";
      if (red == 255 && green == 255 && blue == 255) {
        return;
      }
      var r = doFade(red),
        g = doFade(green),
        b = doFade(blue),
        that = this;
      repeat = function () {
        that.fadeUp(element, r, g, b);
      };
      element.fade = setTimeout(repeat, 100);
    },
    doTest: function (x) {
      console.log(x);
      return x;
    },
    log: (v) => console.log(v),
    getHTTPObject: () => {
      var xmlhttp = false;
      if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
      } else if (window.ActiveXObject) {
        try {
          xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
          try {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
          } catch (e) {
            xmlhttp = false;
          }
        }
      }
      return xmlhttp;
    },
    getAjaxHelpers: (regxx = []) => {
      let fubar = function (x, y, reg) {
        let pass = x.match(reg);
        if (pass) {
          return y.match(reg);
        } else {
          return !y.match(reg);
        }
      };
      return {
        verify: function (canvas, newpage) {
          let lepublic = /class=['"\s]*public['"\s]*/,
            admin = /id=['"\s]*admin['"\s]*/,
            home = /id=['"\s]*home['"\s]*/,
            photos = /id=['"\s]*photos['"\s]*/,
            txt = canvas.innerHTML,
            ispublic = fubar(newpage, txt, lepublic),
            ishome = ispublic && fubar(newpage, txt, home),
            isphoto = ispublic && fubar(newpage, txt, photos),
            pass = [ispublic, ishome, isphoto].reduce((agg, cur) =>
              agg ? agg : cur
            );
          return !pass && fubar(newpage, txt, admin);
        },
        processResponse: processResponse,
        fromPost: fromPost,
        includeLinks: function (a) {
          let reg = new RegExp(window.location.hostname, "i"),
            str = a && a.href,
            invoker = curryL3((o, m, v) => o[m](v))(str)("match");
          if (!str || !str.match(reg)) {
            return false;
          } else {
            return !regxx.some(invoker);
          }
        },
        includeForms: function (form, name = "cancel") {
          let els = form.elements,
            i = 0,
            pass = true;

          while (els[i]) {
            if (pass) {
              if (
                els[i].type === "submit" &&
                els[i].name.toLowerCase() === name
              ) {
                pass = false;
              }
            }
            i++;
          }
          return pass;
        },
        //https://codepen.io/jkphl/pen/AgGYJw
        makeExternal: function (link) {
          var url = link.getAttribute("href"),
            host = window.location.hostname.toLowerCase(),
            regex = new RegExp(
              "^(?:(?:f|ht)tp(?:s)?:)?//(?:[^@]+@)?([^:/]+)",
              "im"
            ),
            match = url.match(regex),
            domain = (
              match ? match[1].toString() : url.indexOf(":") < 0 ? host : ""
            ).toLowerCase();

          // Same domain
          if (domain != host) {
            link.className = "outbound";
            link.setAttribute("target", "_blank");
          }
        },
      };
    },
    displayError: (element, errortext) => {
      var para = document.createElement("div"),
        message = document.createTextNode(errortext);
      para.id = "error";
      para.appendChild(message);
      element.insertBefore(para, meta.$("hollywood"));
      utils.fadeUp(para, 204, 51, 102);
    },
    doAppend: (canvas, request) => {
      // canvas.innerHTML = request.responseText;
      var frag = document.createDocumentFragment(),
        n = document.createElement("div");
      n.innerHTML = request.responseText;
      while (n.firstChild) {
        frag.appendChild(n.firstChild);
      }
      canvas.appendChild(frag);
    },
    getComputedStyle: function (element, property) {
      const toCamelCase = function (variable) {
        return variable.replace(/-([a-z])/g, function (str, letter) {
          return letter.toUpperCase();
        });
      };
      element = getRes(element);
      if (!element || !property) {
        return null;
      }
      let computedStyle = null,
        def = document.defaultView || window;
      if (typeof element.currentStyle !== "undefined") {
        computedStyle = element.currentStyle;
      } else if (
        def &&
        def.getComputedStyle &&
        isFunction(def.getComputedStyle)
      ) {
        computedStyle = def.getComputedStyle(element, null);
      }
      if (computedStyle) {
        try {
          return (
            computedStyle.getPropertyValue(property) ||
            computedStyle.getPropertyValue(toCamelCase(property))
          );
        } catch (e) {
          return (
            computedStyle[property] || computedStyle[toCamelCase(property)]
          );
        }
      }
    },
    payment: payment,
    drillDown: (arr) => {
      var a = arr && arr.slice && arr.slice();
      if (a && a.length > 0) {
        return function drill(o, i) {
          // console.log(arr, o)
          i = isNaN(i) ? 0 : i;
          var prop = a[i];
          if (prop && a[(i += 1)]) {
            return o && drill(o[prop], i);
          }
          return o && o[prop];
        };
      }
      return function (o) {
        return o;
      };
    },
    checkIfImageExists: checkIfImageExists,
    triggerEvent: function (el, type) {
      if (el) {
        var e;
        if ("createEvent" in document) {
          // modern browsers, IE9+
          e = document.createEvent("HTMLEvents");
          e.initEvent(type, false, true);
          el.dispatchEvent(e);
        } else {
          // IE 8
          e = document.createEventObject();
          e.eventType = type;
          el.fireEvent("on" + e.eventType, e);
        }
      }
    },
    //https://stackoverflow.com/questions/55381509/not-able-to-delete-cookies-after-setting-them
    deleteAllCookies: (str) => {
      let mecookies = document.cookie.split(";");
      mecookies = mecookies.map((c) => c.trim());

      for (let i = 0; i < mecookies.length; i++) {
        const cookie = mecookies[i],
          eqPos = cookie.indexOf("="),
          name = eqPos > -1 ? cookie.substring(0, eqPos).trim() : cookie.trim();
        if ((str && name === str) || !str) {
          document.cookie =
            name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
        }
      }
    },
    setCookie: (cname, cvalue, exdays = 1) => {
      const d = new Date();
      d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000);
      let expires = "expires=" + d.toUTCString();
      if (!exdays && meta.isBoolean(exdays)) {
        expires = "path=/";
      } else {
        expires += ";path=/";
      }
      // document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
      document.cookie = cname + "=" + cvalue + ";" + expires;
    },
    getCookie: (cname) => {
      let name = cname + "=",
        decodedCookie = decodeURIComponent(document.cookie),
        ca = decodedCookie.split(";");
      for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == " ") {
          c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
          return c.substring(name.length, c.length);
        }
      }
      return "";
    },
    report(arg) {
      var w = window.viewportSize.getWidth();
      document.getElementsByTagName("h2")[0].innerHTML =
        typeof arg !== "undefined" ? arg : w;
    },
  };
})();
