/*jslint nomen: true */
/*global window: false */
/*global poloAfrica: false */
/*global _: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}

(function (logo_paths) {
  "use strict";

  function doInsert(ancr, refnode, node) {
    if (!ancr && refnode) {
      ancr = refnode.parentNode;
    }
    return ancr.insertBefore(node, refnode);
  }

  function removeElement(node) {
    if (node && node.parentNode) {
      return node.parentNode.removeChild(node);
    }
  }

  function applyArg(f, arg) {
		arg = arg && _.isArray(arg) ? arg : [arg];
		return f.apply(null, arg);
	}


  //CRUCIAL if the gif has been removed halt proceedings
  function getGif() {
    let grp = meta.toArray(document.images),
      reg = /\.gif$/;
    let ret = grp.filter((el) => el.src.match(reg));
    return ret[0] ? ret[0] : null;
  }

  let urlParams = window.URLSearchParams
    ? new window.URLSearchParams(window.location.search)
    : {};
  urlParams.has === urlParams.has || getUrlParameter;

  var utils = poloAfrica.utils,
    meta = poloAfrica.meta,
    $ = meta.$,
    noOp = () => {},
    ptL = meta.doPartial(),
    twice = meta.curryRight(2),
    thrice = meta.curryRight(3),
    curryDefer = meta.curryRight(1, true),
    comp = meta.compose,
    doAlternate = meta.doAlternate(),
    methodInvokePair = (m, p, v, o) => o[m](p, v),
    methodInvoke = (m, v, o) => o[m](v),
    invoke = (f, o) => f(o),
    invokeEach = (funcs, o) => {
      funcs.forEach((f) => f(o));
      return o;
    },
    modulo = (i, n) => i % n,
    getClassList = twice(meta.getter)("classList"),
    doResume = comp(utils.remKlas("paused"), getClassList).wrap(meta.pass),
    doPause = comp(utils.addKlas("paused"), getClassList).wrap(meta.pass),
    section = comp(twice(meta.getter)(2), meta.$Q)("section", true),
    article = section.querySelector("article"),
    anCr = utils.append(),
    insertNode = ptL(doInsert, null, article),
    setAttrs = ptL(methodInvokePair, "setAttribute"),
    getAttr = ptL(methodInvoke, "getAttribute"),
    doSrc = setAttrs("src"),
    doImageAttrs = meta.pApply(invokeEach, [
      setAttrs("alt", "logo"),
      doSrc(logo_paths[0]),
      setAttrs("id", "flower"),
    ]),
    intro = "biff...",
    tweener = getGif(),
    doAni = comp(
      twice(invoke)(null),
      ptL(doInsert),
      insertNode,
      utils.setId("ani").wrap(meta.pass),
      utils.doMake
    ),
    maybeAni = ptL(
      meta.doBest,
      [meta.pApply(doAni, "aside"), meta.always("")],
      meta.identity
    ),
    doFlower = maybeAni(tweener)(),
    anime = $("ani"),
    fader = (function (j, t, gif) {
      if (gif) {
        var t,
          domod = comp(twice(modulo), twice(meta.getter)("length"))(logo_paths),
          baseElement = comp(doImageAttrs, doFlower)(new Image()),
          fadeElement = baseElement.cloneNode(false),
          swapElement = baseElement.cloneNode(false),
          doFade = (i) => {
            fadeElement.style["opacity"] = i / 100;
            return setTimeout(curryDefer(fader)(i), 9);
          },
          exit = () => {
            window.clearTimeout(t);
            t = null;
            exit.opacity = fadeElement.style["opacity"];
            fadeElement.style["opacity"] = 1;
            doPause(anime);
          },
          enter = () => {
            t = 1;
            doFade(exit.opacity);
            doResume(anime);
          };

        removeElement(tweener);
        anime.appendChild(fadeElement);

        doSrc(logo_paths[j], baseElement);
        //CRUCIAL set opacity AFTER image swap from base
        fadeElement.onload = function () {
          this.style["opacity"] = 100;
          j = domod((j += 1));
          doSrc(logo_paths[j], baseElement);
        };

        $("ani").addEventListener("click", doAlternate([exit, enter]));

        var href = ['href', '.'],
        xit = ['id', 'exit'],
        cross = ['txt', 'close'],
        head = meta.$Q('header'),
        anc = utils.getTargetNode(head.firstChild, /^a$/i, 'firstChild'),
        noz = (o,m,v) => o[m](v),   
        foo = (m,o,v) => o[m](v),
        copy = document.createTextNode('Good Evening'),
        para = utils.doMake('p'),
        link = utils.doMake('a'),
        apCopy = thrice(noz)(copy)('appendChild'),
        apPara = thrice(noz)(para)('appendChild'),
        apLink = thrice(noz)(link)('appendChild'),
        soAppend = ptL(foo, 'appendChild');
      // let x = comp(apCopy, apPara, utils.getParent, apLink, soAppend(head), utils.doMake)('div');

        return function (i) {
          i -= 1;
          if (t) {
            if (i >= 0) {
              t = doFade(i);
            } else {
              doSrc(getAttr("src", baseElement), fadeElement);
              setTimeout(curryDefer(fader)(101), 3000);
            }
          }
        };
      }
      return noOp;
    })(0, 1, tweener);


  setTimeout(curryDefer(fader)(101), 2222);
})([
  "/resources/images/articles/fullsize/poloafrica_flower_logo.jpg",
  "/resources/images/articles/fullsize/polo150yrs_squared_logo.jpg",
  "/resources/images/articles/fullsize/polo_armed_forces_logo.jpg",
]);
//hard coded paths above

//fade
// swap on aux
//fade to aux
//aside appendChild(first_child)
