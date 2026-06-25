"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["resources_js_plugins_ddrDrawing_src_shapes_ThinLink_js"],{

/***/ "./resources/js/plugins/ddrDrawing/src/shapes/ThinLink.js":
/*!****************************************************************!*\
  !*** ./resources/js/plugins/ddrDrawing/src/shapes/ThinLink.js ***!
  \****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/**
 * Кастомная тонкая линия без стрелок
 */
console.log('=== ThinLink.js loaded ===');
var ThinLink = window.joint.dia.Link.define('ThinLink', {
  markup: [{
    tagName: 'path',
    selector: 'wrapper',
    attributes: {
      'fill': 'none',
      'cursor': 'pointer',
      'stroke': 'transparent',
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      'stroke-width': '10'
    }
  }, {
    tagName: 'path',
    selector: 'line',
    attributes: {
      'fill': 'none',
      'pointer-events': 'none'
    }
  }],
  attrs: {
    wrapper: {
      connection: true
    },
    line: {
      connection: true,
      stroke: '#ff0000',
      // Красный для отладки
      strokeWidth: 3,
      // Толще для отладки
      fill: 'none',
      strokeLinecap: 'round',
      strokeLinejoin: 'round'
    }
  }
}, {// Статические методы
});
console.log('=== ThinLink defined ===', ThinLink);
/* harmony default export */ __webpack_exports__["default"] = (ThinLink);

/***/ })

}]);