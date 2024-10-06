/*jslint nomen: true */
/* eslint-disable indent */
/* eslint-disable no-param-reassign */
/*global poloAfrica: false */
if (!window.poloAfrica) {
  window.poloAfrica = {};
}
poloAfrica.markup = (function () {
  function charFinder(tgt) {
    return function finder(str, from, i) {
      if (str.slice(from - 1).charAt(0) === tgt) {
        return finder(str, from - 1, i + 1);
      } else {
        return i;
      }
    };
  }

  function getInlineTag() {
    let tag = window.prompt("please provide a name for the tag, eg:", "span"),
      alltags =
        "a,abbr,acronym,audio,b,bdi,bdo,big,br,button,canvas,cite,code,data,datalist,del,dfn,em,embed,i,iframe,img,input,ins,kbd,level,map,mark,meter,noscript,object,output,picture,progress,q,ruby,s,samp,script,select,slot,small,span,strong,sub,sup,svg,template,textarea,time,u,var,video,wbr.",
      tags =
        "abbr,acronym,audio,bdi,bdo,big,button,canvas,cite,code,data,datalist,del,dfn,iframe,ins,kbd,level,map,mark,meter,noscript,object,output,picture,progress,q,ruby,s,samp,script,select,slot,small,span,sub,sup,svg,template,textarea,time,u,var,video",
      self = "br,embed,input,wbr.",
      mytag = tag.toLowerCase();
    return tag && tags.indexOf(mytag) !== -1 ? tag : null;
  }

  /*
  we land here because of a missing link REFERENCE "[1]:#"
  and are unable to determine the next available refrence index [1]
  The actual link text title MAY be ok or
  it could be missing completely or
  without link markup ###My Title not ###[MyTitle][1]*/

  function fixLinkRefs(t, myhash = "###") {
    let mytitle = tx.value.match(/^#+\[.+\]\[1\]/),
      repl = `[${t}][1]\n`,
      pass,
      [_, hash, text] = tx.value.match(/(#+)(.+)/) ?? [],
      i = 0;
    if (!mytitle) {
      repl = hash ? (hash += repl) : repl;
    }
    if (text && !mytitle) {
      if (t !== text) {
        pass = confirm(
          "Title Mismatch! replace heading text with article title?",
          "yes"
        );
      }
      if (pass) {
        tx.value = tx.value.replace(/^(#+)([\w\s]+)\n/, "$1[" + t + "][1]\n");
        i = t.length - text.length;
      } else {
        tx.value = tx.value.replace(/^(#+)([\w\s]+)\n/, `$1[$2][1]\n`);
      }
      /*string can be anything that passes the isNaN test and is FIVE characters long
     equating to the missing markup characters which are [][1]*/
      repl = "[][1]";
      if (i) {
        //adjust string length if we have a mismatch as a result of the confirm box
        repl =
          i > 0
            ? repl.padStart(repl.length + i, "#")
            : repl.substring(Math.abs(i));
      }
    } else {
      if (!mytitle) {
        tx.value = myhash + repl + tx.value;
      } else {
        repl = "";
      }
    }
    tx.value += `\n[1]:#`;
    return repl;
  }

  function foo(ancr, tag) {
    let data = [
      ["heading", "Create headings from h1 thru h6"],
      ["bold", "toggle bold text"],
      ["ital", "toggle italic text"],
      ["para", "paragraph shortcut"],
      ["line", "line break shortcut"],
      ["span", "inline tag shortcut"],
      ["link", "create a link from selected text"],
      ["unlink", "unlink selected text"],
      ["list", "toggle from paragraph to list"],
      ["img", "insert an image"],
      ["revert", "clear all edits"],
      ["help", "toggle a handy guide"],
    ];

    let mytag = document.createElement(tag);
    ancr.appendChild(tag);
  }

  const isOL = /\n+1\.\s+([^\n]+)/g,
    isUL = /\n+-+\s([^\n]+)/g,
    control_data = [
      ["heading", "Create headings from h1 thru h6"],
      ["bold", "toggle bold text"],
      ["ital", "toggle italic text"],
      ["para", "paragraph shortcut"],
      ["line", "line break shortcut"],
      ["span", "inline tag shortcut"],
      ["link", "create a link from selected text"],
      ["unlink", "unlink selected text"],
      ["list", "toggle from paragraph to list"],
      ["img", "insert an image"],
      ["revert", "clear all edits"],
      ["help", "toggle a handy guide"],
    ];

  let mylist = [
      [isOL, "- $1\n"],
      [isUL, "1. $1\n"],
    ],
    tog = false;

  const meta = poloAfrica.meta,
    invokeMethod = meta.invokeMethod,
    curry3 = meta.curryRight(3),
    curry2 = meta.curryRight(2),
    curryL2 = meta.curryLeft(2),
    doMatch = (m) => (v, o) => o[m](v),
    mittleFactory = meta.mittelFactory,
    ptL = meta.doPartial(),
    comp = meta.compose,
    $ = meta.$,
    log = meta.log,
    doAlt = meta.doAlternate(),
    pass = meta.pass,
    getClassList = curry2(meta.getter)("classList"),
    addKlas = ptL(meta.invokeMethodBridge, "add"),
    remKlas = ptL(meta.invokeMethodBridge, "remove"),
    undoActive = comp(remKlas("active"), getClassList).wrap(pass),
    doActive = comp(addKlas("active"), getClassList).wrap(pass),
    add = (a, b) => a + b,
    starFind = charFinder("*"),
    invokePair = (o, m, v) => o[m].apply(o, v),
    mittleInvoke = mittleFactory(),
    isEqual = function (char) {
      return function (arg) {
        return arg === char;
      };
    },
    Maker = (tx) => {
      // if(!tx) { return {} };
      let cache = tx.value,
        header = 0;
      const emphasis = /\**([^*]+)\**/g,
        doPair = curry3(invokePair),
        soMatch = doMatch("match"),
        isEmpty = () =>
          /^\s$/.test(subSelect(tx.selectionStart, tx.selectionEnd)),
        isSpace = isEqual(" "),
        isLine = isEqual("\n"),
        isStop = isEqual("."),
        isPipe = ptL(soMatch, /\|/),
        isStar = ptL(soMatch, /\*/),
        endlinkref = /\[(\d)+\]:.+/g,
        isOpeningBracket = ptL(soMatch, /\[/),
        matchLine = ptL(soMatch, /\n/),
        isNotWord = ptL(soMatch, /\W/),
        leadingSlash = ptL(soMatch, /^\//),
        isClosingBracket = ptL(soMatch, /\]/),
        getCharAt = mittleInvoke(invokeMethod, "charAt"),
        hasEmphasis = comp(isEqual("*"), getCharAt),
        x2Bi = doPair([emphasis, "***$1***"])("replace"), //italic OR bold to bold italic
        bi2I = doPair([emphasis, "*$1*"])("replace"),
        bi2B = doPair([emphasis, "**$1**"])("replace"),
        setItal = comp(curry2(add)("*"), curryL2(add)("*")),
        setBold = comp(curry2(add)("**"), curryL2(add)("**")),
        setSpan = comp(curry2(add)("|"), curryL2(add)("|")),
        resetLocalFormat = doPair([emphasis, "$1"])("replace"),
        isSelected = (a, b) => a !== b,
        subSelect = (from, to) => tx.value.slice(from, to),
        trimFrom = (str, from) => (/^\s+[^ ]+/.test(str) ? from + 1 : from),
        trimTo = (str, to) => (/\s+$/.test(str) ? to - 1 : to),
        fixFrom = (cb, n, k = 0) => {
          while (!cb(tx.value.slice(n - 1, n)) && n) {
            k++;
            n--;
          }
          return k;
        },
        fixTo = (cb, n, k = 0) => {
          while (!cb(tx.value.slice(n, n + 1)) && n <= tx.value.length) {
            k++;
            n++;
          }
          return k;
        },
        charCount = (str, char) => {
          let i = 0;
          if (!char) {
            return;
          }
          while (str.charAt(i) === char) {
            i += 1;
          }
          return i;
        },
        fixSelection = (doFrom, doTo, flag = false) => {
          doTo = doTo || doFrom;
          let from = tx.selectionStart,
            to = tx.selectionEnd,
            selected = from !== to,
            selection = subSelect(from, to);
          from = trimFrom(selection, from);
          to = trimTo(selection, to);
          from -= fixFrom(doFrom, from);
          to += fixTo(doTo, to);
          selection = subSelect(from, to);
          selected = from !== to;
          return {
            from,
            to,
            selection,
            selected,
          };
        },
        fixCursorPos = (f, n) => {
          /*maybe this first check is over the top as it ASSUMES someone deliberately attempting to link on a link by placing the cursor 1 or 2 points before ie prevword |[mylink] || prevword| [mylink] the other checks are for the absent-minded, no that is a link, no you don't need to unlink*/
          if (f(subSelect(n, n + 1)) || f(subSelect(n + 1, n + 2))) {
            return false;
          }
          return true;
        },
        queryLinkSelection = (f1, f2, n) => {
          let i = fixFrom(f1, n),
            j = fixFrom(f2, n);
          return i < j;
        },
        hasFirstWord = (n) => {
          let i = fixFrom(isSpace, n),
            j = fixFrom(isLine, n);
          return j < i;
        },
        hasLastWord = (n) => {
          let i = fixTo(isSpace, n),
            j = fixTo(isLine, n);
          return j < i;
        },
        postFocus = (start, end) => {
          //used with onChange event, not onSubmit
          tx.selectionStart = start || tx.selectionStart;
          tx.selectionEnd = end || tx.selectionEnd;
        },
        setTextArea = (from, to, selection) => {
          tx.value = subSelect(0, from) + selection + subSelect(to);
        },
        bailOut = (list, selection) => {
          return list.some((char) => selection === char);
        },
        //required if no selection and in first para, will search in vain for a full stop
        search = (f1, f2) => {
          let { from, to, selection, selected } = fixSelection(f1),
            //https://davidwalsh.name/destructuring-alias
            { from: start, to: end } = fixSelection(f2),
            ret = {
              from: Math.max(from, start),
              to: Math.min(to, end),
              ...{ selection, selected },
            };
          return ret;
        },
        resolveSelection = (route1, route2) => {
          let selection,
            o = search(route1, route2 || route1),
            { from, to } = o,
            offset = starFind(tx.value, from, 0);
          //maybe zero
          from -= offset;
          to += offset;
          selection = subSelect(from, to);
          return {
            from,
            to,
            selection,
          };
        },
        checkDoubleIndex = (to) => {
          let str = subSelect(to - 4, to),
            next = Number(str.slice(1, -1));
          return !isNaN(next);
        },
        setLinkIndex = (str, i) => {
          let linebreak = i === 1 ? "\n\n[" : "\n[";
          return str + linebreak + i + "]: ";
        },
        sortLinkAttributes = (flag) => (flag ? " {target=_blank}" : ""),
        sortLinkType = (str) => (leadingSlash(str) ? str.substring(1) : str),
        getCurrentLinkRef = () => {
          var i,
            j,
            lastitem,
            res = tx.value.match(endlinkref);

          if (res) {
            lastitem = res[res.length - 1];
            i = 0;
            j = 0;
          }

          //[1][2]... [10]
          if (lastitem) {
            i = Number(lastitem.slice(1, 2));
            j = Number(lastitem.slice(1, 3));
            //j will be NaN 9] until we get to 10]
            //we will not be entertaining the prospect of 100] links per article
            return isNaN(j) ? i + 1 : j + 1;
          } else {
            var title = tx.form.title.value;
            return fixLinkRefs(title);
          }
        },
        getReferenceDef = (n) => "[" + n + "]:",
        setReferenceDef = (str, i) => "[" + str + "][" + i + "]",
        setLinkTitle = (str) => {
          let t = str.indexOf(" "),
            title;
          if (t >= 0) {
            title = '"' + str.substring(t + 1) + '"';
            return str.substring(0, t + 1) + title;
          }
          return str;
        },
        //is this required???
        unlinkFix = (from, to, selection) => {
          //postFocus(to - 1, to);
          //to = !isNaN(selection.slice(-1)) ? to + 1 : to ;
          //postFocus(from, to);
          //selection = subSelect(from, to);
          return { from, to, selection };
        },
        sortRefLinks = () => {
          let o = fixSelection(isOpeningBracket, isClosingBracket),
            from = o.from - 1,
            to = o.to,
            char = subSelect(to + 3, to + 4),
            reg = /^\[\d+\]/,
            selection;
          //not QUITE robust; a page with a lot of links could have a second numeral [10]
          to += isClosingBracket(char) ? 4 : 5;
          selection = subSelect(from, to);
          if (reg.test(selection)) {
            return null;
          }

          return unlinkFix(from, to, selection);
        },
        listFromLine = () => {
          let o = fixSelection(isLine),
            F = o.from,
            T = o.to,
            copy = tx.value,
            doTextArea = ptL(setTextArea, F - 1, T),
            str = copy.slice(F - 1, T),
            [[rule, rpl]] = mylist;
          if (str.match(/(\d\.\s|-\s)/)) {
            /*EDGE CASE: if some fiend was editing multiple lists alternately things
             can get outtawhack, tog should be true at this point*/
            if (!tog) {
              mylist = mylist.reverse();
              [[rule, rpl]] = mylist;
            }
            doTextArea(str.replace(rule, "\n$1"));
            tog = false;
          } else {
            doTextArea(str.replace(/([^\n]+)(\n*)/g, rpl));
            tx.value = tx.value.replace(/([^\n]+\n)(\n)\n+/g, "$1$2");
            mylist = mylist.reverse();
            tog = !tog;
          }
        },
        //methods:
        boldy = () => {
          if (isEmpty()) {
            return;
          }
          let { from, to, selection } = resolveSelection(isNotWord),
            isActive = curryL2(hasEmphasis)(selection),
            doTextArea = ptL(setTextArea, from, to);
          if (bailOut(["|", "_", "[", "!"], subSelect(from - 1, from))) {
            return;
          }
          //tx.parentNode);
          if (isActive(0)) {
            //bold, italics, both
            ({ from, to, selection } = resolveSelection(isNotWord, isStar));
            doTextArea = ptL(setTextArea, from, to);
            if (!isActive(1)) {
              //italics
              doTextArea(x2Bi(selection));
              postFocus(from + 3, to + 1);
            } else if (isActive(2)) {
              //bold italics
              doTextArea(bi2I(selection));
              postFocus(from + 1, to - 5);
            } else {
              //bold
              doTextArea(resetLocalFormat(selection));
              postFocus(from, to - 4);
            }
          } else {
            doTextArea(setBold(selection));
            postFocus(from + 2, to + 2);
          }
          tx.focus(); //issue on some browsers
        },
        italy = () => {
          let { from, to, selection } = resolveSelection(isNotWord),
            isActive = curryL2(hasEmphasis)(selection),
            doTextArea = ptL(setTextArea, from, to);
          if (bailOut(["|", "_", "[", "!"], subSelect(from - 1, from))) {
            return;
          }
          if (isActive(0)) {
            //bold, italics, both
            ({ from, to, selection } = resolveSelection(isNotWord, isStar));
            doTextArea = ptL(setTextArea, from, to);
            if (!isActive(1)) {
              //italics
              doTextArea(resetLocalFormat(selection));
              postFocus(from, to - 2);
            } else if (isActive(2)) {
              //bold italics
              doTextArea(bi2B(selection));
              postFocus(from + 2, to - 4);
            } else {
              //bold
              doTextArea(x2Bi(selection));
              postFocus(from + 3, to - 1);
            }
          } else {
            //normal
            doTextArea(setItal(selection));
            postFocus(from + 1, to + 1);
          }
          tx.focus();
        },
        spanner = () => {
          let myopen,
            myclose,
            length,
            { from, to, selected, selection } = fixSelection(
              isSpace,
              isSpace,
              true
            ),
            doTextArea = ptL(setTextArea, from, to);
          if (bailOut(["*", "_", "[", "!"], selection.charAt(0))) {
            return;
          }
          if (selection.charAt(0) === "|") {
            if (selected) {
              return doTextArea(selection.slice(1, -1));
            } else {
              length = to - from;
              myopen = from + length;
              myclose = fixTo(to, isPipe);
              return setTextArea(
                from,
                myopen + myclose + 1,
                selection.slice(1)
              );
            }
          }
          let res = selection.match(/^<([^>]+)>/),
            tag = res ? res[1] : null;
          if (tag) {
            let i = tag.length,
              l = selection.length,
              myselection = selection.substring(i + 2, l - (i + 3)),
              open = new RegExp("<[^>]+>(?=" + myselection + ")"),
              end = new RegExp("(?<=" + myselection + ")</[^>]+>");
            tx.value = tx.value.replace(open, "");
            tx.value = tx.value.replace(end, "");
          } else {
            doTextArea(setSpan(selection));
            let tag = getInlineTag(1);
            if (tag) {
              let opentag = `<${tag}>`,
                endtag = `</${tag}>`,
                open = new RegExp("\\|(?=" + selection + ")"),
                end = new RegExp("(?<=" + selection + ")\\|");
              tx.value = tx.value.replace(open, opentag);
              tx.value = tx.value.replace(end, endtag);
            }
          }
        },
        lister = () => {
          let from = tx.selectionStart,
            to = tx.selectionEnd,
            list = ["|", "_", "*", "!", "["],
            copy = tx.value;
          if (
            !isSelected(from, to) ||
            bailOut(list, copy.slice(from, from + 1))
          ) {
            return;
          }
          if (matchLine(copy.slice(from, to))) {
            return listFromLine();
          } else {
            //initial from space delimited: list a b c
            setTextArea(
              from,
              to,
              copy.slice(from - 1, to).replace(/(\w+(\s|$))/g, "- $1\n")
            );
            //remove first newline
            //tx.value = tx.value.replace(/\n+(\W+\w+)/, "\n$1");
            mylist = mylist.reverse();
            tog = true;
          }
        },
        linker = () => {
          //Let's NOT ASSUME selectionStart ARE the same (ie we are dealing with a selection) tx.selectionEnd and start the search from the appropriate place
          let i,
            first = hasFirstWord(tx.selectionStart),
            last = hasLastWord(tx.selectionEnd),
            doFrom = first ? isLine : isSpace,
            doTo = last ? isLine : isSpace;
          if (queryLinkSelection(doFrom, isOpeningBracket, tx.selectionStart)) {
            let page = $("page").value,
              attrs = window.prompt(
                "Enter hyperlink for/" + page,
                "https://www.bbc.co.uk"
              );

            if (attrs) {
              let { from, to, selection } = fixSelection(doFrom, doTo),
                external = attrs.indexOf("http");
              attrs = setLinkTitle(attrs);
              i = getCurrentLinkRef(selection);
              /*
              return created title ###My Missing Heading[1]
              to fix situation where the copy is missing one
              */
              if (!i || isNaN(i)) {
                from += i.length;
                to += i.length;
                i = 2;
              }

              tx.value =
                tx.value.slice(0, from) +
                setReferenceDef(selection, i) +
                setLinkIndex(tx.value.slice(to), i) +
                sortLinkType(attrs) +
                sortLinkAttributes(external >= 0);
            }
          }
        },
        //https://gist.github.com/rxaviers/7360908
        image = () => {
          let t,
            title,
            res,
            { from, to, selection } = fixSelection(isLine);
          selection = subSelect(from, to);
          if (bailOut(["|", "_", "*", "["], selection.charAt(0))) {
            return;
          }
          res = window.prompt(
            "Enter path to image, replace {file.jpg} with img filename",
            "/resources/images/articles/fullsize/{file.jpg}"
          );
          if (res) {
            //res = res.replace(/["']/, '');
            t = res.lastIndexOf(" ");
            if (t >= 0) {
              title = '"' + res.substring(t + 1) + '"';
              res = res.substring(0, t + 1);
              res = res + " " + title;
            }
            setTextArea(from, to, "![" + selection + "](" + res + ")");
          }
        },
        invoke = (f) => f(),
        noOp = () => {},
        notEmpty = meta.deferCB(meta.best, isEmpty),
        toggleToolbar = doAlt([doActive, undoActive]);
      return {
        heading: function () {
          if (!tx.value.match(/^#/)) {
            if(tx.value.match(endlinkref)){
              tx.value = tx.value.replace(endlinkref, '');
            }
            return fixLinkRefs(tx.form.title.value);
          }

          let o = fixSelection(isLine);
          header = charCount(subSelect(o.from, o.to), "#");
          header += 1;
          if (header === 7) {
            setTextArea(
              o.from,
              o.to,
              "#" + subSelect(o.from, o.to).replace(/#/g, "")
            );
            header = 1;
          } else {
            setTextArea(o.from, o.to, "#" + subSelect(o.from, o.to));
          }
          postFocus(o.from, o.from);
          tx.focus();
          tx.selectionEnd = o.from;
        },
        //all these are useless if an empty space was selected
        bold: comp(invoke, notEmpty([noOp, boldy])),
        ital: comp(invoke, notEmpty([noOp, italy])),
        span: comp(invoke, notEmpty([noOp, spanner])),
        list: comp(invoke, notEmpty([noOp, lister])),
        link: comp(invoke, notEmpty([noOp, linker])),
        img: comp(invoke, notEmpty([noOp, image])),
        //reference style links
        unlink: function () {
          tx.focus();
          if (
            queryLinkSelection(isOpeningBracket, isSpace, tx.selectionStart)
          ) {
            var o = sortRefLinks();
            if (o) {
              var n,
                { from, to, selection } = o,
                double = checkDoubleIndex(to),
                i = double ? 5 : 4,
                j = double ? 4 : 3,
                referenceDef = subSelect(to - j, to),
                next = Number(referenceDef.slice(1, -1));
              //body text
              tx.value =
                subSelect(0, from) + selection.slice(1, -i) + subSelect(to);
              //deal with refs[1]:
              to = tx.value.indexOf(referenceDef);
              //check if there are other refs that follow:
              n = tx.value.indexOf(getReferenceDef(next + 1));
              if (n > 0) {
                tx.value = subSelect(0, to) + subSelect(n);
              } else {
                tx.value = subSelect(0, to);
              }
            }
          }
        },
        para: function () {
          var o = search(isStop, isLine);
          if (!o.selected) {
            //advance cursor to keep period with pre-selected text
            o.to += 1;
          }
          setTextArea(o.from, o.to, "\n\n" + tx.value.slice(o.from, o.to));
          //general tidy
          tx.value = tx.value.replace(/\n\s+/g, "\n\n");
        },
        line: function () {
          var o = search(isStop, isLine);
          if (!o.selected) {
            //advance cursor to keep period with pre-selected text
            o.to += 1;
          }
          setTextArea(o.from, o.to, "<br> " + tx.value.slice(o.from, o.to));
        },
        revert: function () {
          tx.value = cache;
        },
        help: function () {
          let guide = $("guide"),
            help = $("help");
          toggleToolbar(guide);
          help.onclick = (e) => {
            toggleToolbar(guide);
          };
        },
        setCount: function (count) {
          this.count = count;
        },
      }; //ret
    }, //eof Maker,
    markup = (el) => {
      let maker = Maker(el);
      return (e) => {
        let id = e.target.alt;
        if (e.target.type === "submit") {
          return;
        }
        e.preventDefault();
        if (id) {
          let func = maker[id.toLowerCase()];
          meta.getResult(maker[id.toLowerCase()]);
        }
      };
    };
  return markup;
})();
