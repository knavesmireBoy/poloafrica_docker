/*jslint nomen: true */
/*global window: false */
/*global document: false */
/*global Modernizr: false */
/*global poloAfrica: false */
/*global _: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}

(function () {
  "use strict";

  function checkAll() {
    var grp = [].slice.call(this.form.elements),
      checked,
      boxes = grp
        .filter((el) => el.type.match(/checkbox/i))
        .filter((el) => el.id !== "all")
        .filter((el) => el.id !== "backup");
    checked = this.checked;
    boxes.forEach((el) => (el.checked = checked));
  }

  function checkArticlePosition(data) {
    //force reload if page order changes, otherwise we're out of whack
    let res = data.match(/position=(\d)/);
    log(33, res, data);
    return res ? Number(res[1]) : res;
  }

  function on_submit(sz = 666) {
    if (document.getElementById("upload").files[0].size > sz) {
      alert("File is too big.");
      return false;
    }
    return true;
  }

  function displayLoading() {
    var image = document.createElement("img"),
      element = document.querySelector(".pic");
    image.setAttribute("alt", "loading...");
    image.setAttribute("src", "/resources/images/dev/progressbar.gif");
    image.className = "loading";
    if (element) {
      element.appendChild(image);
    }
  }
  //https://codeshack.io/file-upload-progress-bar-js-php/
  function uploadProgress(e) {
    let calc = () => Math.round((e.loaded / e.total) * 100),
      el = document.querySelector(".uploadpreview form"),
      button = el.querySelector("input[type=submit]");
    if (el) {
      el.style.background =
        "linear-gradient(to right, #54008b, #54008b " +
        calc() +
        "%, #176b3d " +
        calc() +
        "%)";
    }
    button.value = "Uploading... " + "(" + calc().toFixed(2) + "%)";
  }

  function removeGuide(e) {
    let res = confirm(
      "Clicking OK will hide the guide, you can manually restore it or restart the browser."
    );
    if (res) {
      utils.setCookie("upload_guide", "upload_guide", false);
    }
    return res;
  }

  function restoreGuide(e) {
    utils.deleteAllCookies("upload_guide");
  }

  var meta = poloAfrica.meta,
    utils = poloAfrica.utils,
    log = console.log,
    defer = meta.doPartial(true),
    invoke = (f, a) => f(a),
    thunk = f => f(),
    curry2 = meta.curryRight(2),
    curry3 = meta.curryRight(3),
    getMyLink = curry3(utils.getTargetNode)("parentNode")(/^a$/i),
    helpers = utils.getAjaxHelpers([
      /gallery\/display/,
      /resources\/assets/,
      /mailto/,
      /\w+\/$/,
      /^#/,
    ]);

  function Hijax() {
    var canvas,
      callback,
      container,
      errorhandler,
      loading,
      url,
      data,
      request,
      timer,
      submitValidators = [checkArticlePosition],
      ajaxClickCB = function (e) {
        let a = getMyLink(e.target);
        if (!a || a.nodeName !== "A") {
          return false;
        }
        url += a.getAttribute("href");
        return !start();
      },
      ajaxWrapper = function (orig, e) {
        if (e.target.id === "exit_guide") {
          let res = removeGuide();
          if (!res) {
            return false;
          }
        }
        if (e.target.id === "restore_guide") {
          restoreGuide();
        }
        return orig(e);
      },
      ajaxCBWrap = ajaxClickCB.wrap(ajaxWrapper),
      completeRequest = function (e) {
        if (request.readyState == 4) {
          if (request.status == 200 || request.status == 304) {
            clearTimeout(timer);
            if (canvas) {
              canvas.innerHTML = "";
              helpers.processResponse(canvas, request);
            }
            meta.getResult(callback);
          } else {
            meta.getResult(errorhandler);
          }
        }
      },
      initiateRequest = function () {
        // meta.getResult(loading);
        //4
        timer = setTimeout(function () {
          request.onreadystatechange = function () {};
          request.abort();
          meta.getResult(errorhandler);
        }, 60000);

        request.onreadystatechange = completeRequest;
        if (data) {
          request.open("POST", url, true);
          request.setRequestHeader(
            "Content-Type",
            "application/x-www-form-urlencoded"
          );
          request.send(data);
        } else {
          request.open("GET", url, true);
          request.send(null);
        }
      },
      start = function () {
        request = utils.getHTTPObject();
        if (request && url.match(/\/\w+/i)) {
          initiateRequest();
          return true;
        } else {
          return false;
        }
      },
      postFormData = function (form) {
        if (typeof FormData === "undefined") {
          throw new Error("FormData is not implemented");
        }
        request = utils.getHTTPObject();

        request.upload.addEventListener("progress", uploadProgress);
        request.open("POST", url);
        request.onreadystatechange = completeRequest;
        var formdata = new FormData(form);
        request.send(formdata);
      },
      isAutoSub = function (el) {
        return el.classList.contains("autosub");
      },
      //took a while to work out
      getAutoSub = function (container) {
        if (isAutoSub(container)) {
          let select = container.querySelector("select"),
            number = container.querySelector("input[type=number]"),
            els = [select, number].filter((item) => item);
          els.forEach((el) => (el.onchange = (e) => e.target.form.onsubmit(e)));
        }
      },
      mysubmit = function (e) {
        e.stopPropagation();
        let tgt = e.target.elements ? e.target : e.target.form,
        res;
        [data] = helpers.fromPost(tgt);
        res = submitValidators.every(curry2(invoke)(data));
        return res || !start();
      };

    function Constr() {}

    Constr.prototype = {
      constructor: Constr,
      setCallback: (value) => (callback = value),
      setCanvas: (value) => (canvas = value),
      setContainer: (value) => (container = value),
      setErrorHandler: (value) => (errorhandler = value),
      setLoading: (value) => (loading = value),
      setUrl: (value) => (url = value),
      captureData: () => {
        if (container) {
          if (container.elements) {
            if (container.enctype && container.enctype.match("multipart")) {
              container.onsubmit = function (e) {
                e.stopPropagation();
                e.preventDefault();
                postFormData(e.target);
              };
            } else {
              container.onsubmit = mysubmit;
              getAutoSub(container);
            }
          } else {
            container.onclick = ajaxCBWrap;
          }
        }
      },
    };
    return new Constr();
  }

  var init = function (element) {
    var path = "",
      xhr = Hijax(),
      path =
        element && element.elements
          ? path + element.getAttribute("action")
          : path,
      record = document.getElementById("records"),
      setrecord = document.getElementById("setrecords"),
      ajaxCB = () => {
        var forms = document.forms,
          links = meta.$Q("#content a", true),
          controls = document.getElementById("controls"),
          tx = document.getElementById("tx"),
          i = 0;

        for (i = 0; i < forms.length; i++) {
          if (helpers.includeForms(forms[i])) {
            init(forms[i]);
          }
        }
        for (i = 0; i < links.length; i++) {
          if (helpers.includeLinks(links[i])) {
            init(links[i]);
          }
        }
        if (controls && !controls.onclick && tx) {
          controls.onclick = poloAfrica.markup(tx);
        }
      },
      checkbox = meta.$("all");
    if (checkbox) {
      checkbox.onchange = checkAll;
    }
    setrecord && setrecord.appendChild(record);
    xhr.setContainer(element);
    xhr.setCanvas(document.body);
    xhr.setCallback(ajaxCB);
    /*
    xhr.setLoading(function () {
     displayLoading();
    });
    */
    xhr.setUrl(path);
    xhr.captureData();
    if (!element) {
      ajaxCB();
    }
  };
  addEventListener("DOMContentLoaded", defer(init, null));
})();
