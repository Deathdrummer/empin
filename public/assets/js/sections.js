/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/sections/site/contracts/calcGencontracting.js":
/*!********************************************************************!*\
  !*** ./resources/js/sections/site/contracts/calcGencontracting.js ***!
  \********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "calcGencontracting": function() { return /* binding */ calcGencontracting; }
/* harmony export */ });
function calcGencontracting() {
  var ops = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var percentNds = ops.percentNds,
      contractingPercent = ops.contractingPercent;
  var selfPriceNds = $('#selfPriceNds').ddrCalc([{
    selector: '#subPriceNds',
    method: 'percent',
    percent: contractingPercent,
    reverse: true
  }, {
    selector: '#subPrice',
    method: 'percent',
    percent: contractingPercent,
    reverse: true,
    middleware: [function (value, calc) {
      return calc('nds', $('#subPriceNds').val(), percentNds, true);
    }, false]
  }]);
  var selfPrice = $('#selfPrice').ddrCalc([{
    selector: '#subPrice',
    method: 'percent',
    percent: contractingPercent,
    reverse: true
  }, {
    selector: '#subPriceNds',
    method: 'percent',
    percent: contractingPercent,
    reverse: true,
    middleware: [function (value, calc) {
      return calc('nds', $('#subPrice').val(), percentNds);
    }, false]
  }]);
  var subPriceNds = $('#subPriceNds').ddrCalc([{
    selector: '#subPrice',
    method: 'nds',
    percent: percentNds,
    reverse: true
  }, {
    selector: '#selfPriceNds',
    method: 'percent',
    percent: contractingPercent //reverse: true,

  }, {
    selector: '#selfPrice',
    method: 'percent',
    percent: contractingPercent,
    //reverse: true,
    middleware: [function (value, calc) {
      return calc('nds', $('#selfPriceNds').val(), percentNds, true);
    }, false]
  }]);
  var subPrice = $('#subPrice').ddrCalc([{
    selector: '#subPriceNds',
    method: 'nds',
    percent: percentNds
  }, {
    selector: '#selfPrice',
    method: 'percent',
    percent: contractingPercent //reverse: true,

  }, {
    selector: '#selfPriceNds',
    method: 'percent',
    percent: contractingPercent,
    //reverse: true,
    middleware: [function (value, calc) {
      return calc('nds', $('#selfPrice').val(), percentNds);
    }, false]
  }]);
  return {
    selfPriceNds: selfPriceNds,
    selfPrice: selfPrice,
    subPriceNds: subPriceNds,
    subPrice: subPrice
  };
}

/***/ }),

/***/ "./resources/js/sections/site/contracts/calcSubcontracting.js":
/*!********************************************************************!*\
  !*** ./resources/js/sections/site/contracts/calcSubcontracting.js ***!
  \********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "calcSubcontracting": function() { return /* binding */ calcSubcontracting; }
/* harmony export */ });
function calcSubcontracting() {
  var ops = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var percentNds = ops.percentNds,
      contractingPercent = ops.contractingPercent;
  var selfPriceNds = $('#selfPriceNds').ddrCalc([{
    selector: '#genPriceNds',
    method: 'percent',
    percent: contractingPercent
  }, {
    selector: '#genPrice',
    method: 'percent',
    percent: contractingPercent,
    middleware: [function (value, calc) {
      return calc('nds', $('#genPriceNds').val(), percentNds, true);
    }, false]
  }]);
  var selfPrice = $('#selfPrice').ddrCalc([{
    selector: '#genPrice',
    method: 'percent',
    percent: contractingPercent
  }, {
    selector: '#genPriceNds',
    method: 'percent',
    percent: contractingPercent,
    middleware: [function (value, calc) {
      return calc('nds', $('#genPrice').val(), percentNds);
    }, false]
  }]);
  var genPriceNds = $('#genPriceNds').ddrCalc([{
    selector: '#genPrice',
    method: 'nds',
    percent: percentNds,
    reverse: true
  }, {
    selector: '#selfPriceNds',
    method: 'percent',
    percent: contractingPercent,
    reverse: true
  }, {
    selector: '#selfPrice',
    method: 'percent',
    percent: contractingPercent,
    reverse: true,
    middleware: [function (value, calc) {
      return calc('nds', $('#selfPriceNds').val(), percentNds, true);
    }, false]
  }]);
  var genPrice = $('#genPrice').ddrCalc([{
    selector: '#genPriceNds',
    method: 'nds',
    percent: percentNds
  }, {
    selector: '#selfPrice',
    method: 'percent',
    percent: contractingPercent,
    reverse: true
  }, {
    selector: '#selfPriceNds',
    method: 'percent',
    percent: contractingPercent,
    reverse: true,
    middleware: [function (value, calc) {
      return calc('nds', $('#selfPrice').val(), percentNds);
    }, false]
  }]);
  return {
    selfPriceNds: selfPriceNds,
    selfPrice: selfPrice,
    genPriceNds: genPriceNds,
    genPrice: genPrice
  };
}

/***/ }),

/***/ "./resources/js/sections/site/contracts/index.js":
/*!*******************************************************!*\
  !*** ./resources/js/sections/site/contracts/index.js ***!
  \*******************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "calcGencontracting": function() { return /* reexport safe */ _calcGencontracting_js__WEBPACK_IMPORTED_MODULE_1__.calcGencontracting; },
/* harmony export */   "calcSubcontracting": function() { return /* reexport safe */ _calcSubcontracting_js__WEBPACK_IMPORTED_MODULE_0__.calcSubcontracting; },
/* harmony export */   "data": function() { return /* binding */ data; }
/* harmony export */ });
/* harmony import */ var _calcSubcontracting_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./calcSubcontracting.js */ "./resources/js/sections/site/contracts/calcSubcontracting.js");
/* harmony import */ var _calcGencontracting_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./calcGencontracting.js */ "./resources/js/sections/site/contracts/calcGencontracting.js");


var data = 'dfgfdfg';

/***/ }),

/***/ "./resources/js/sections sync recursive ^\\.\\/.*\\/index\\.js$":
/*!*********************************************************!*\
  !*** ./resources/js/sections/ sync ^\.\/.*\/index\.js$ ***!
  \*********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./site/contracts/index.js": "./resources/js/sections/site/contracts/index.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./resources/js/sections sync recursive ^\\.\\/.*\\/index\\.js$";

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!**********************************!*\
  !*** ./resources/js/sections.js ***!
  \**********************************/
window.loadSectionScripts = function () {
  var ops = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  if (_.isEmpty(ops)) throw Error('loadSectionScripts -> не переданы параметры!');

  var _$assign = _.assign({
    guard: 'site',
    section: null
  }, ops),
      guard = _$assign.guard,
      section = _$assign.section; //console.log(get);


  if (_.isNull(section)) throw Error('loadSectionScripts -> не указан параметр section');

  var data = __webpack_require__("./resources/js/sections sync recursive ^\\.\\/.*\\/index\\.js$")("./" + guard + '/' + section + "/index.js");

  return data;
};
}();
/******/ })()
;