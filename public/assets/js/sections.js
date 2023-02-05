/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@babel/runtime/regenerator/index.js":
/*!**********************************************************!*\
  !*** ./node_modules/@babel/runtime/regenerator/index.js ***!
  \**********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

module.exports = __webpack_require__(/*! regenerator-runtime */ "./node_modules/regenerator-runtime/runtime.js");


/***/ }),

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

/***/ "./resources/js/sections/site/contracts/contextMenu.js":
/*!*************************************************************!*\
  !*** ./resources/js/sections/site/contracts/contextMenu.js ***!
  \*************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "contextMenu": function() { return /* binding */ contextMenu; }
/* harmony export */ });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);


function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function contextMenu(haSContextMenu, selectedContracts, removeContractsRows, sendMessStat, lastChoosedRow, canEditCell, canCreateCheckbox, canRemoveCheckbox) {
  var commentsTooltip, cellEditTooltip;

  $.contractContextMenu = function (_ref, contractId, departmentId, selectionId, objectNumber, title, hasDepsToSend, messagesCount, searched, selectionEdited, isArchive, canToArchive, // отправка договора в архив
  canSending, // отправка договора в другой отдел из отдела
  canSendingAll, // отправка договора в другой отдел из общего списка
  canHiding, // скрыть договор
  canChat, // просмотр чата
  canChatSending, // возможность отправлять сообщения в чате
  canReturnToWork // вернуть договор в работу из архива
  ) {
    var _selectedContracts$it;

    var target = _ref.target,
        closeOnScroll = _ref.closeOnScroll,
        onContextMenu = _ref.onContextMenu,
        changeAttrData = _ref.changeAttrData,
        buildTitle = _ref.buildTitle;
    var isCommon = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('commonlist') || false;
    var isDeptCheckbox = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('deptcheck') || false;
    var hasCheckbox = !!$(target.pointer).closest('[ddrtabletd]').children().length;
    var contextEdited = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('contextedit');
    var disableEditCell = !$(target.pointer).closest('[ddrtabletd]').attr('contextedit');
    onContextMenu(function () {
      var _commentsTooltip, _cellEditTooltip;

      haSContextMenu.value = true;
      $('#contractsList').find('[editted]').each(function (k, cell) {
        unEditCell(cell);
      }); // если кликнуть на НЕвыделенном договоре - то все выделенния отменятся и выделится текущий кликнутый договор

      if (isCommon && $(target.selector).hasAttr('contractselected') == false) {
        $('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
        lastChoosedRow.value = target.selector; //$(target.selector).addClass('ddrtable__tr-selected').setAttrib('contractselected');

        selectedContracts.add($(target.selector).attr('contractid'));
      } // Если клик НЕ на таблице общего перечня


      if (!isCommon) {
        $('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected'); // lastChoosedRow.value = target.selector;
        // $(target.selector).addClass('ddrtable__tr-selected').setAttrib('contractselected');
        //selectedContracts.add($(target.selector).attr('contractid'));
      }

      console.log('onContextMenu', selectedContracts.items);
      if (((_commentsTooltip = commentsTooltip) === null || _commentsTooltip === void 0 ? void 0 : _commentsTooltip.destroy) != undefined) commentsTooltip.destroy();
      if (((_cellEditTooltip = cellEditTooltip) === null || _cellEditTooltip === void 0 ? void 0 : _cellEditTooltip.destroy) != undefined) cellEditTooltip.destroy();
    });
    var countSelected = ((_selectedContracts$it = selectedContracts.items) === null || _selectedContracts$it === void 0 ? void 0 : _selectedContracts$it.length) || 0;

    function unEditCell() {
      var cell = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
      if (_.isNull(cell)) return;

      if ($(cell).find('#edittedCellData').tagName() == 'input') {
        $(cell).find('[edittedplace]').number(true, 2, '.', ' ');
        $(cell).find('[hiddenplace]').removeAttrib('hidden');
      }

      $(cell).removeClass('editted');
      $(cell).find('[edittedwait]').remove();
      $(cell).find('[edittedplacer]').remove();
      $(cell).find('[edittedblock]').remove();
      $(cell).removeAttrib('editted');
    }

    closeOnScroll('#contractsList');
    return [{
      name: buildTitle(countSelected, 'Чат договора', 'Cообщение в чаты'),
      countLeft: countSelected > 1 ? countSelected : null,
      countRight: countSelected == 1 ? messagesCount : null,
      countOnArrow: true,
      visible: isCommon && canChat,
      sort: 1,
      onClick: function onClick() {
        if (countSelected == 1) {
          // Если выделен 1 договор
          ddrPopup({
            title: '<small class="fz12px color-gray">Чат договора:</small> «' + title + '»',
            width: 800,
            buttons: ['Закрыть'],
            winClass: 'ddrpopup_chat'
          }).then(function (_ref2) {
            var state = _ref2.state,
                wait = _ref2.wait,
                setTitle = _ref2.setTitle,
                setButtons = _ref2.setButtons,
                loadData = _ref2.loadData,
                setHtml = _ref2.setHtml,
                setLHtml = _ref2.setLHtml,
                dialog = _ref2.dialog,
                close = _ref2.close,
                onScroll = _ref2.onScroll,
                disableButtons = _ref2.disableButtons,
                enableButtons = _ref2.enableButtons,
                setWidth = _ref2.setWidth;
            wait();
            axiosQuery('get', 'site/contracts/chat', {
              contract_id: contractId
            }).then(function (_ref3) {
              var data = _ref3.data,
                  error = _ref3.error,
                  status = _ref3.status,
                  headers = _ref3.headers;

              if (error) {
                $.notify('Не удалось загрузить чат!', 'error');
                console.log(error === null || error === void 0 ? void 0 : error.message, error === null || error === void 0 ? void 0 : error.errors);
                return;
              }

              setHtml(data, function () {
                sendMessStat.value = false;
                wait(false);
                $('.chat__message').tripleTap(function (elem) {
                  selectText(elem);
                });
                var chatVisibleHeight = $('#chatMessageList').outerHeight(),
                    chatScrollHeight = $('#chatMessageList')[0].scrollHeight;
                $('#chatMessageList').scrollTop(chatScrollHeight - chatVisibleHeight);
                $('#chatMessageBlock').focus();
                $('#chatMessageList').find('.chat__post').mouseup(function (e) {
                  var selObj = window.getSelection();
                  var selectString = selObj.toString();

                  if (selectString.length) {
                    copyStringToClipboard(selObj.toString());
                    $.notify('Скопировано!');
                  }
                });
                $('#chatMessageBlock').ddrInputs('change', function () {
                  var mess = getContenteditable('#chatMessageBlock');

                  if (mess && !sendMessStat.value) {
                    sendMessStat.value = true;
                    $('#chatSendMesageBtn').ddrInputs('enable');
                  } else if (!mess && sendMessStat.value) {
                    sendMessStat.value = false;
                    $('#chatSendMesageBtn').ddrInputs('disable');
                  }
                });
              });
            })["catch"](function (e) {
              console.log(e);
            });
          });
        } else {
          // Если выделено более 1 договора - отправить сообщение в чаты с выделеными договорами
          var html = '<p class="d-block mb5px fz14px color-darkgray">Сообщение:</p>' + '<div class="textarea normal-textarea w100" id="sendMessagesToManyContractsField">' + '<textarea name="" rows="10" class="w100"></textarea>' + '</div>';
          ddrPopup({
            title: 'Отправить сообщение в выбранные договоры',
            width: 500,
            html: html,
            buttons: ['Закрыть', {
              title: 'Отправить',
              variant: 'blue',
              action: 'sendMessagesToManyContracts',
              disabled: 1,
              id: 'sendMessagesToManyContractsBtn'
            }],
            winClass: 'ddrpopup_chat'
          }).then(function (_ref4) {
            var state = _ref4.state,
                wait = _ref4.wait,
                setTitle = _ref4.setTitle,
                setButtons = _ref4.setButtons,
                loadData = _ref4.loadData,
                setHtml = _ref4.setHtml,
                setLHtml = _ref4.setLHtml,
                dialog = _ref4.dialog,
                close = _ref4.close,
                onClose = _ref4.onClose,
                onScroll = _ref4.onScroll,
                disableButtons = _ref4.disableButtons,
                enableButtons = _ref4.enableButtons,
                setWidth = _ref4.setWidth;
            var isEmpty = true;
            $('#sendMessagesToManyContractsField').find('textarea').focus();
            $('#sendMessagesToManyContractsField').ddrInputs('change', function (textarea) {
              if ($(textarea).val() && isEmpty) {
                $('#sendMessagesToManyContractsBtn').ddrInputs('enable');
                isEmpty = false;
              } else if (!$(textarea).val() && !isEmpty) {
                $('#sendMessagesToManyContractsBtn').ddrInputs('disable');
                isEmpty = true;
              }
            });

            $.sendMessagesToManyContracts = function () {
              wait();
              $('#sendMessagesToManyContractsBtn').ddrInputs('disable');
              var message = $('#sendMessagesToManyContractsField').find('textarea').val();
              var sendMessAbortCtrl = new AbortController();
              axiosQuery('put', 'site/contracts/chats', {
                contractIds: selectedContracts.items,
                message: message
              }, 'json', sendMessAbortCtrl).then(function (_ref5) {
                var data = _ref5.data,
                    error = _ref5.error,
                    status = _ref5.status,
                    headers = _ref5.headers;

                if (error) {
                  $.notify('Ошибка отправки сообщения!', 'error');
                  return;
                }

                if (data) {
                  if (data == -1) {
                    $.notify('Сообщение не было разослано!', 'info');
                  } else {
                    $.notify('Сообщение успешно отправлено во все чаты выбранных договоров!');
                    close();
                  }
                } else {
                  $.notify('Не удалось отправить сообщение в чаты выбранных договоров!', 'error');
                  wait(false);
                }
              })["catch"](function (e) {
                console.log(e);
              });
              onClose(function () {
                sendMessAbortCtrl.abort();
              });
            };
          });
        }
      }
    }, {
      name: 'Отправить в архив',
      visible: isCommon && canToArchive && !isArchive,
      countLeft: countSelected > 1 ? countSelected : null,
      sort: 5,
      onClick: function onClick() {
        var html = '';
        html += '<div>';

        if (countSelected == 1) {
          html += '<p class="fz14px color-darkgray text-start">Номер объекта: <span class="color-black">' + objectNumber + '</span></p>';
          html += '<p class="fz14px color-darkgray text-start">Название/заявитель: <span class="color-black">' + title + '</span></p>';
          html += '<p class="fz18px color-red mt15px">Вы действительно хотите отправить договор в архив?</p>';
        } else if (countSelected > 1) {
          html += '<p class="fz18px color-red mt15px">' + buildTitle(countSelected, 'Вы действительно хотите отправить # % в архив?', ['договор', 'договора', 'договоров']) + '</p>';
        }

        html += '</div>';
        ddrPopup({
          width: 400,
          // ширина окна
          html: html,
          // контент
          buttons: ['ui.cancel', {
            title: 'Отправить',
            variant: 'red',
            action: 'contractToArchiveAction'
          }],
          centerMode: true,
          winClass: 'ddrpopup_dialog'
        }).then(function (_ref6) {
          var close = _ref6.close,
              wait = _ref6.wait;

          $.contractToArchiveAction = function (_) {
            wait();
            axiosQuery('post', 'site/contracts/to_archive', {
              contractIds: selectedContracts.items
            }, 'json').then(function (_ref7) {
              var data = _ref7.data,
                  error = _ref7.error,
                  status = _ref7.status,
                  headers = _ref7.headers;
              var contractTitle = countSelected == 1 ? ' ' + objectNumber + ' ' + title : '';

              if (data) {
                if (selectionId || searched) {
                  getCounts(function () {
                    removeContractsRows(target);
                  });
                } else {
                  removeContractsRows(target);
                }

                $.notify(buildTitle(countSelected, '%' + contractTitle + ' успешно отправлен в архив!', '# % успешно отправлены в архив!', ['Договор', 'договора', 'договоров']));
              } else {
                $.notify(buildTitle(countSelected, 'Ошибка! %' + contractTitle + ' не был отправлен в архив!', 'Ошибка! % не были отправлены в архив!', ['договор', 'договора', 'договоров']), 'error');
              }

              close();
            });
          };
        });
      }
    }, {
      name: 'Вернуть в работу',
      visible: isCommon && canReturnToWork && isArchive,
      countLeft: countSelected > 1 ? countSelected : null,
      sort: 5,
      onClick: function onClick() {
        ddrPopup({
          width: 400,
          // ширина окна
          html: '<p class="fz18px color-green">' + buildTitle(countSelected, 'Вы действительно хотите вернуть # % в работу?', ['договор', 'договора', 'договоров']) + '</p>',
          // контент
          buttons: ['ui.cancel', {
            title: 'Вернуть',
            variant: 'blue',
            action: 'returnContractToWorkBtn'
          }],
          centerMode: true,
          winClass: 'ddrpopup_dialog'
        }).then(function (_ref8) {
          var close = _ref8.close,
              wait = _ref8.wait;

          $.returnContractToWorkBtn = function (_) {
            wait();
            axiosQuery('post', 'site/contracts/to_work', {
              contractIds: selectedContracts.items
            }, 'json').then(function (_ref9) {
              var data = _ref9.data,
                  error = _ref9.error,
                  status = _ref9.status,
                  headers = _ref9.headers;
              var contractTitle = countSelected == 1 ? ' ' + objectNumber + ' ' + title : '';

              if (data) {
                if (selectionId || searched) {
                  getCounts(function () {
                    removeContractsRows(target);
                  });
                } else {
                  removeContractsRows(target);
                }

                $.notify(buildTitle(countSelected, '%' + contractTitle + ' успешно возвращен в работу!', '# % успешно возвращены в работу!', ['Договор', 'договора', 'договоров'])); //target.changeAttrData(15, '0');
              } else {
                $.notify(buildTitle(countSelected, 'Ошибка! %' + contractTitle + ' не был возвращен в работу!', 'Ошибка! # % не были возвращены в работу!', ['Договор', 'договора', 'договоров']), 'error');
              }

              close();
            });
          };
        });
      }
    }, {
      name: 'Добавить в подборку',
      //hidden: selectionId,
      visible: isCommon,
      countLeft: countSelected > 1 ? countSelected : null,
      sort: 2,
      load: {
        url: 'site/contracts/selections_to_choose',
        params: {
          contractIds: selectedContracts.items
        },
        method: 'get',
        map: function map(item) {
          return {
            name: item.title,
            //faIcon: 'fa-solid fa-clipboard-check',
            disabled: !!item.choosed,
            onClick: function onClick(selector) {
              var selectionId = item.id;
              var procNotif = processNotify(buildTitle(countSelected, 'Добавление # % в подборку...', ['договора', 'договоров', 'договоров']));
              var contractIds = selectedContracts.items;
              var params, method;

              if (contractIds.length == 1) {
                params = {
                  contractId: contractIds[0],
                  selectionId: selectionId
                };
                method = 'add_contract';
              } else {
                params = {
                  contractIds: contractIds,
                  selectionId: selectionId
                };
                method = 'add_contracts';
              }

              axiosQuery('put', 'site/selections/' + method, params).then(function (_ref10) {
                var data = _ref10.data,
                    error = _ref10.error,
                    status = _ref10.status,
                    headers = _ref10.headers;

                if (error) {
                  procNotif.error({
                    message: 'Ошибка добавления в подборку!'
                  });
                  console.log(error === null || error === void 0 ? void 0 : error.message, error.errors);
                } else {
                  var contractTitle = countSelected == 1 ? ' ' + objectNumber + ' ' + title : '';
                  procNotif.done({
                    message: buildTitle(countSelected, '%' + contractTitle + ' успешно добавлен в подборку!', '# % успешно добавлены в подборку!', ['Договор', 'договора', 'договоров'])
                  });
                }
              });
            }
          };
        }
      }
    }, {
      name: 'Удалить из подборки',
      visible: isCommon && selectionId,
      countLeft: countSelected > 1 ? countSelected : null,
      sort: 4,
      onClick: function onClick() {
        var procNotif = processNotify(buildTitle(countSelected, 'Удаление # % из подборки...', ['договора', 'договоров', 'договоров']));
        var contractIds = selectedContracts.items;
        var params, method;

        if (contractIds.length == 1) {
          params = {
            contractId: contractIds[0],
            selectionId: selectionId
          };
          method = 'remove_contract';
        } else {
          params = {
            contractIds: contractIds,
            selectionId: selectionId
          };
          method = 'remove_contracts';
        }

        axiosQuery('put', 'site/selections/' + method, params).then(function (_ref11) {
          var data = _ref11.data,
              error = _ref11.error,
              status = _ref11.status,
              headers = _ref11.headers;
          var contractTitle = countSelected == 1 ? ' ' + objectNumber + ' ' + title : '';

          if (error) {
            //$.notify('Ошибка удаления из подборки!', 'error');
            procNotif.error({
              message: 'Ошибка удаления договора' + contractTitle + ' из подборки!'
            });
            console.log(error === null || error === void 0 ? void 0 : error.message, error.errors);
          } else {
            procNotif.done({
              message: buildTitle(countSelected, '%' + contractTitle + ' успешно удален из подборки!', '# % успешно удалены из подборки!', ['Договор', 'договора', 'договоров'])
            }); //target.changeAttrData(7, '0');

            getCounts(function () {
              removeContractsRows(target);
              $('[selectionsbtn]').ddrInputs('enable');
            });
          }
        });
      }
    }, {
      name: 'Создать новую подборку',
      sort: 3,
      visible: isCommon,
      countLeft: countSelected > 1 ? countSelected : null,
      onClick: function onClick() {
        var html = '<p class="d-block mb5px fz14px color-darkgray">Название подборки:</p>' + '<div class="input normal-input normal-input-text w100">' + '<input type="text" value="" id="selectionNameInput" placeholder="Введите текст" autocomplete="off" inpgroup="normal">' + '<div class="normal-input__errorlabel noselect" errorlabel=""></div>' + '</div>';
        ddrPopup({
          title: 'Создать подборку из выбранных договоров',
          width: 500,
          html: html,
          buttons: ['Закрыть', {
            title: 'Создать',
            variant: 'blue',
            action: 'createNewSelection',
            disabled: 1,
            id: 'createNewSelectionBtn'
          }],
          winClass: 'ddrpopup_chat'
        }).then(function (_ref12) {
          var state = _ref12.state,
              wait = _ref12.wait,
              setTitle = _ref12.setTitle,
              setButtons = _ref12.setButtons,
              loadData = _ref12.loadData,
              setHtml = _ref12.setHtml,
              setLHtml = _ref12.setLHtml,
              dialog = _ref12.dialog,
              close = _ref12.close,
              onClose = _ref12.onClose,
              onScroll = _ref12.onScroll,
              disableButtons = _ref12.disableButtons,
              enableButtons = _ref12.enableButtons,
              setWidth = _ref12.setWidth;
          var isEmpty = true;
          $('#selectionNameInput').ddrInputs('change', function (input) {
            if ($(input).val() && isEmpty) {
              $('#createNewSelectionBtn').ddrInputs('enable');
              isEmpty = false;
            } else if (!$(input).val() && !isEmpty) {
              $('#createNewSelectionBtn').ddrInputs('disable');
              isEmpty = true;
            }
          });

          $.createNewSelection = function () {
            wait();
            var title = $('#selectionNameInput').val();
            var newSelectionAbortCtrl = new AbortController();
            axiosQuery('post', 'site/selections/add_selection_from_contextmenu', {
              title: title,
              contractIds: selectedContracts.items
            }, 'json', newSelectionAbortCtrl).then(function (_ref13) {
              var data = _ref13.data,
                  error = _ref13.error,
                  status = _ref13.status,
                  headers = _ref13.headers;

              if (error) {
                $.notify('Ошибка создания подборки!', 'error');
                return;
              }

              if (data) {
                if (data == -1) {
                  $.notify('Подборка не была создана!', 'info');
                  wait(false);
                } else {
                  $.notify(buildTitle(countSelected, 'Поодборка с # % была успешна создана!', ['договором', 'договорами', 'договорами']));
                  close();
                }
              } else {
                $.notify('Не удалось создать подборку!', 'error');
                wait(false);
              }
            })["catch"](function (e) {
              console.log(e);
            });
            onClose(function () {
              newSelectionAbortCtrl.abort();
            });
          };
        });
      }
    }, {
      name: 'Отправить в другой отдел',
      enabled: selectedContracts.items.length > 1 || !!hasDepsToSend && (canSending && departmentId || canSendingAll && !departmentId),
      hidden: isArchive || !isCommon,
      countLeft: countSelected > 1 ? countSelected : null,
      sort: 4,
      load: {
        url: 'site/contracts/departments',
        params: {
          contractId: selectedContracts.items.length == 1 ? selectedContracts.items[0] : null
        },
        method: 'get',
        map: function map(item) {
          return {
            name: item.name,
            //faIcon: 'fa-solid fa-angles-right',
            visible: true,
            onClick: function onClick(selector) {
              var departmentName = selector.text(),
                  itemsCount = selector.items().length;
              var procNotif = processNotify('Отправка договора в другой отдел...');
              axiosQuery('post', 'site/contracts/send', {
                contractIds: selectedContracts.items,
                departmentId: item.id
              }, 'json').then(function (_ref14) {
                var data = _ref14.data,
                    error = _ref14.error,
                    status = _ref14.status,
                    headers = _ref14.headers;

                if (data) {
                  //$.notify('Договор успешно отправлен в '+departmentName+'!');
                  if (selectionId || searched) {
                    var params = {};
                    getCounts(function () {//if (currentList > 0) removeContractsRows(target);
                    });
                  } else {//if (currentList > 0) removeContractsRows(target);
                  }

                  if (data.length) {
                    var contractTitle = countSelected == 1 ? ' ' + objectNumber + ' ' + title : '';
                    var mess = buildTitle(data.length, '# %' + contractTitle + ' успешно отправлен в ' + departmentName + '!', '# % успешно отправлены в ' + departmentName + '!', ['договор', 'договора', 'договоров']);
                    procNotif.done({
                      message: mess
                    });
                  } else {
                    procNotif.error({
                      message: 'Ни один договор не был отправлен!'
                    });
                  }

                  if (countSelected == 1 && itemsCount == 0) changeAttrData(6, '0');
                } else {
                  //$.notify('Ошибка! Договор не был отправлен!', 'error');
                  procNotif.error({
                    message: 'Ошибка! Договор не был отправлен!'
                  });
                }
              });
            }
          };
        }
      }
    }, {
      name: 'Скрыть',
      visible: isCommon && canHiding && departmentId && !isArchive,
      countLeft: countSelected > 1 ? countSelected : null,
      sort: 6,
      onClick: function onClick() {
        ddrPopup({
          width: 400,
          // ширина окна
          html: buildTitle(countSelected, '<p class="fz18px color-red">Вы действительно хотите скрыть # %?</p>', ['договор', 'договора', 'договоров']),
          // контент
          buttons: ['ui.cancel', {
            title: 'Скрыть',
            variant: 'red',
            action: 'contractHide'
          }],
          centerMode: true,
          winClass: 'ddrpopup_dialog'
        }).then(function (_ref15) {
          var close = _ref15.close,
              wait = _ref15.wait;

          $.contractHide = function (_) {
            wait();
            axiosQuery('post', 'site/contracts/hide', {
              contractIds: selectedContracts.items,
              departmentId: departmentId
            }, 'json').then(function (_ref16) {
              var data = _ref16.data,
                  error = _ref16.error,
                  status = _ref16.status,
                  headers = _ref16.headers;
              var contractTitle = countSelected == 1 ? ' ' + objectNumber + ' ' + title : '';

              if (data) {
                if (selectionId || searched) {
                  getCounts(function () {
                    removeContractsRows(target);
                  });
                } else {
                  removeContractsRows(target);
                }

                $.notify(buildTitle(countSelected, '%' + contractTitle + ' успешно скрыт!', '# % успешно скрыты!', ['Договор', 'договора', 'договоров'])); //target.changeAttrData(9, '0');
              } else {
                $.notify(buildTitle(countSelected, 'Ошибка! договор' + contractTitle + ' не был скрыт!', 'Ошибка! договоры не были скрыты!'), 'error');
              }

              close();
            });
          };
        });
      }
    }, {
      name: hasCheckbox && canRemoveCheckbox ? 'Удалить чекбокс' : !hasCheckbox && canCreateCheckbox ? 'Добавить чекбокс' : '',
      visible: isDeptCheckbox && !isArchive && (!hasCheckbox && canCreateCheckbox || hasCheckbox && canRemoveCheckbox),
      sort: 1,
      onClick: function onClick() {
        return _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee() {
          var cell, edited, attrData, _pregSplit, _pregSplit2, _pregSplit2$, contractId, _pregSplit2$2, departmentId, _pregSplit2$3, stepId, waitCell, _yield$axiosQuery, data, error, status, headers, randId, editedCheckbox;

          return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee$(_context) {
            while (1) {
              switch (_context.prev = _context.next) {
                case 0:
                  cell = $(target.pointer).closest('[ddrtabletd]');
                  edited = !!$(cell).attr('edited');
                  attrData = $(cell).attr('deptcheck');
                  _pregSplit = pregSplit(attrData), _pregSplit2 = _slicedToArray(_pregSplit, 3), _pregSplit2$ = _pregSplit2[0], contractId = _pregSplit2$ === void 0 ? null : _pregSplit2$, _pregSplit2$2 = _pregSplit2[1], departmentId = _pregSplit2$2 === void 0 ? null : _pregSplit2$2, _pregSplit2$3 = _pregSplit2[2], stepId = _pregSplit2$3 === void 0 ? null : _pregSplit2$3;
                  waitCell = $(cell).ddrWait({
                    iconHeight: '30px',
                    bgColor: '#efe9f9'
                  });
                  _context.next = 7;
                  return axiosQuery('post', 'site/contracts/step_checkbox', {
                    contractId: contractId,
                    departmentId: departmentId,
                    stepId: stepId,
                    value: hasCheckbox
                  }, 'json');

                case 7:
                  _yield$axiosQuery = _context.sent;
                  data = _yield$axiosQuery.data;
                  error = _yield$axiosQuery.error;
                  status = _yield$axiosQuery.status;
                  headers = _yield$axiosQuery.headers;

                  if (!error) {
                    _context.next = 17;
                    break;
                  }

                  console.log(error);
                  $.notify('Ошибка! Не удалось ' + (hasCheckbox ? 'удалить' : 'добавить') + ' чекбокс!', 'error');
                  waitCell.destroy();
                  return _context.abrupt("return");

                case 17:
                  // canCreateCheckbox canRemoveCheckbox
                  if (data) {
                    if (!hasCheckbox) {
                      if (edited) {
                        randId = generateCode('nnnnnnn');
                        editedCheckbox = '<div class="checkbox normal-checkbox">' + '<input type="checkbox" name="assigned_primary" id="checkbox' + randId + '" inpgroup="normal" oninput="$.contractSetData(this, ' + contractId + ',' + departmentId + ',' + stepId + ',1)">' + '<label class="noselect" for="checkbox' + randId + '"></label>' + '<label for="checkbox' + randId + '" class="checkbox__label lh90 d-inline-block normal-checkbox__label noselect"></label>' + '<div class="normal-checkbox__errorlabel" errorlabel=""></div>' + '</div>';
                        $(cell).html(editedCheckbox);
                      } else {
                        $(cell).html('<div class="checkbox-empty checkbox-empty-normal border-gray-400"></div>');
                      }

                      $.notify('Чекбокс успешно добавлен!');
                    } else {
                      $(cell).empty();
                      $.notify('Чекбокс успешно удален!');
                    }

                    waitCell.destroy();
                  }

                case 18:
                case "end":
                  return _context.stop();
              }
            }
          }, _callee);
        }))();
      }
    }, {
      name: 'Комментарии',
      visible: hasCheckbox && isDeptCheckbox,
      sort: 2,
      onClick: function onClick() {
        return _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee4() {
          var cell, attrData, _pregSplit3, _pregSplit4, _pregSplit4$, contractId, _pregSplit4$2, departmentId, _pregSplit4$3, stepId;

          return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee4$(_context4) {
            while (1) {
              switch (_context4.prev = _context4.next) {
                case 0:
                  cell = $(target.pointer).closest('[ddrtabletd]');
                  attrData = $(cell).attr('deptcheck');
                  _pregSplit3 = pregSplit(attrData), _pregSplit4 = _slicedToArray(_pregSplit3, 3), _pregSplit4$ = _pregSplit4[0], contractId = _pregSplit4$ === void 0 ? null : _pregSplit4$, _pregSplit4$2 = _pregSplit4[1], departmentId = _pregSplit4$2 === void 0 ? null : _pregSplit4$2, _pregSplit4$3 = _pregSplit4[2], stepId = _pregSplit4$3 === void 0 ? null : _pregSplit4$3;
                  commentsTooltip = $(cell).ddrTooltip({
                    //cls: 'w44rem',
                    placement: 'bottom',
                    tag: 'noscroll noopen',
                    offset: [0 - 5],
                    minWidth: '200px',
                    minHeight: '200px',
                    duration: [200, 200],
                    trigger: 'click',
                    wait: {
                      iconHeight: '40px'
                    },
                    onShow: function () {
                      var _onShow = _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee3(_ref17) {
                        var reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps, _yield$axiosQuery2, data, error, status, headers, textarea, inputCellCommentTOut;

                        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee3$(_context3) {
                          while (1) {
                            switch (_context3.prev = _context3.next) {
                              case 0:
                                reference = _ref17.reference, popper = _ref17.popper, show = _ref17.show, hide = _ref17.hide, destroy = _ref17.destroy, waitDetroy = _ref17.waitDetroy, setContent = _ref17.setContent, setData = _ref17.setData, setProps = _ref17.setProps;
                                _context3.next = 3;
                                return axiosQuery('get', 'site/contracts/cell_comment', {
                                  contract_id: contractId,
                                  department_id: departmentId,
                                  step_id: stepId
                                }, 'json');

                              case 3:
                                _yield$axiosQuery2 = _context3.sent;
                                data = _yield$axiosQuery2.data;
                                error = _yield$axiosQuery2.error;
                                status = _yield$axiosQuery2.status;
                                headers = _yield$axiosQuery2.headers;
                                _context3.next = 10;
                                return setData(data);

                              case 10:
                                waitDetroy();
                                textarea = $(popper).find('#sendCellComment');
                                $(textarea).focus();
                                textarea[0].selectionStart = textarea[0].selectionEnd = textarea[0].value.length;
                                $('#contractsList').one('scroll', function () {
                                  var _commentsTooltip2;

                                  // При скролле списка скрыть тултип комментариев
                                  if (((_commentsTooltip2 = commentsTooltip) === null || _commentsTooltip2 === void 0 ? void 0 : _commentsTooltip2.destroy) != undefined) commentsTooltip.destroy();
                                });
                                $(textarea).on('input', function () {
                                  var _this = this;

                                  clearTimeout(inputCellCommentTOut);
                                  inputCellCommentTOut = setTimeout( /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee2() {
                                    var comment, _yield$axiosQuery3, postRes, postErr, status, headers;

                                    return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee2$(_context2) {
                                      while (1) {
                                        switch (_context2.prev = _context2.next) {
                                          case 0:
                                            comment = $(_this).val();
                                            _context2.next = 3;
                                            return axiosQuery('post', 'site/contracts/cell_comment', {
                                              contract_id: contractId,
                                              department_id: departmentId,
                                              step_id: stepId,
                                              comment: comment
                                            }, 'json');

                                          case 3:
                                            _yield$axiosQuery3 = _context2.sent;
                                            postRes = _yield$axiosQuery3.data;
                                            postErr = _yield$axiosQuery3.error;
                                            status = _yield$axiosQuery3.status;
                                            headers = _yield$axiosQuery3.headers;

                                            if (!postErr) {
                                              _context2.next = 12;
                                              break;
                                            }

                                            console.log(postErr);
                                            $.notify('Ошибка! Не удалось задать комментарий!', 'error');
                                            return _context2.abrupt("return");

                                          case 12:
                                            if (postRes) {
                                              if (comment) $(reference).append('<div class="trangled trangled-top-right"></div>');else $(reference).find('.trangled').remove(); //$.notify('Комментарий успешно сохранен!');
                                              //$(this).ddrInputs('change');
                                            }

                                          case 13:
                                          case "end":
                                            return _context2.stop();
                                        }
                                      }
                                    }, _callee2);
                                  })), 500);
                                });

                              case 16:
                              case "end":
                                return _context3.stop();
                            }
                          }
                        }, _callee3);
                      }));

                      function onShow(_x) {
                        return _onShow.apply(this, arguments);
                      }

                      return onShow;
                    }(),
                    onDestroy: function onDestroy() {//$(cell).removeAttrib('tooltiped');
                    }
                  });

                case 4:
                case "end":
                  return _context4.stop();
              }
            }
          }, _callee4);
        }))();
      }
    }, {
      name: 'Редактировать',
      visible: countSelected == 1 && isCommon && canEditCell && contextEdited
      /* && !isArchive*/
      ,
      // добавить !isArchive - если не нужно редактировать в архиве 
      disabled: $(target.pointer).closest('[ddrtabletd]').hasAttr('editted') || disableEditCell,
      sort: 7,
      onClick: function onClick() {
        return _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee10() {
          var cell, attrData, _pregSplit5, _pregSplit6, _pregSplit6$, contractId, _pregSplit6$2, column, _pregSplit6$3, type, cellWait, _yield$axiosQuery4, data, error, status, headers, edittedBlock;

          return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee10$(_context10) {
            while (1) {
              switch (_context10.prev = _context10.next) {
                case 0:
                  cell = $(target.pointer).closest('[ddrtabletd]');
                  attrData = $(cell).attr('contextedit');
                  _pregSplit5 = pregSplit(attrData), _pregSplit6 = _slicedToArray(_pregSplit5, 3), _pregSplit6$ = _pregSplit6[0], contractId = _pregSplit6$ === void 0 ? null : _pregSplit6$, _pregSplit6$2 = _pregSplit6[1], column = _pregSplit6$2 === void 0 ? null : _pregSplit6$2, _pregSplit6$3 = _pregSplit6[2], type = _pregSplit6$3 === void 0 ? null : _pregSplit6$3;
                  $('#contractsList').find('[editted]').each(function (k, cell) {
                    unEditCell(cell);
                  });
                  $('#contractsList').on(tapEvent + '.unEditCell', function (e) {
                    if ($(e.target).closest('[ddrtabletd]').hasAttr('editted') && [3, 4].indexOf(type) === -1) return;
                    unEditCell(cell);
                    $('#contractsList').off('.unEditCell');
                  });
                  $(cell).setAttrib('editted');
                  cellWait = $(cell).ddrWait({
                    iconHeight: '30px',
                    tag: 'noscroll noopen edittedwait'
                  });

                  if (!([1, 2].indexOf(type) !== -1)) {
                    _context10.next = 24;
                    break;
                  }

                  _context10.next = 10;
                  return axiosQuery('get', 'site/contracts/cell_edit', {
                    contract_id: contractId,
                    column: column,
                    type: type
                  });

                case 10:
                  _yield$axiosQuery4 = _context10.sent;
                  data = _yield$axiosQuery4.data;
                  error = _yield$axiosQuery4.error;
                  status = _yield$axiosQuery4.status;
                  headers = _yield$axiosQuery4.headers;
                  $(cell).append(data);
                  if (type == 2) $(cell).find('#edittedCellData').number(true, 2, '.', ' ');
                  $(cell).find('#edittedCellData').focus();
                  edittedBlock = $(cell).find('#edittedCellData');
                  edittedBlock[0].selectionStart = edittedBlock[0].selectionEnd = edittedBlock[0].value.length;
                  $(cell).find('#edittedCellData').on('keypress', function (e) {
                    if (e.keyCode == 13 && !e.shiftKey) {
                      $(cell).find('[savecelldata]').trigger(tapEvent);
                    }
                  });
                  $(cell).one(tapEvent, '[savecelldata]', /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee5() {
                    var cellData, emptyVal, _yield$axiosQuery5, data, error, status, headers;

                    return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee5$(_context5) {
                      while (1) {
                        switch (_context5.prev = _context5.next) {
                          case 0:
                            $(this).hide();
                            cellWait.on();
                            cellData = $(cell).find('#edittedCellData').val();
                            emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
                            _context5.next = 6;
                            return axiosQuery('post', 'site/contracts/cell_edit', {
                              contract_id: contractId,
                              column: column,
                              type: type,
                              data: cellData
                            }, 'json');

                          case 6:
                            _yield$axiosQuery5 = _context5.sent;
                            data = _yield$axiosQuery5.data;
                            error = _yield$axiosQuery5.error;
                            status = _yield$axiosQuery5.status;
                            headers = _yield$axiosQuery5.headers;

                            if (error) {
                              cellWait.off();
                              $.notify('Ошибка сохранения ячейки!', 'error');
                              console.log(error === null || error === void 0 ? void 0 : error.message, error.errors);
                            }

                            if (data) {
                              $.notify('Сохранено!');
                              $(cell).find('[edittedplace]').text(cellData || emptyVal);
                              cellWait.destroy();
                              unEditCell(cell);
                            }

                          case 13:
                          case "end":
                            return _context5.stop();
                        }
                      }
                    }, _callee5, this);
                  })));
                  _context10.next = 25;
                  break;

                case 24:
                  if ([3, 4].indexOf(type) !== -1) {
                    // 3 - дата 4 - вып. список
                    $(cell).addClass('editted');
                    cellEditTooltip = $(cell).ddrTooltip({
                      //cls: 'w44rem',
                      placement: 'bottom',
                      tag: 'noscroll noopen nouneditted',
                      offset: [0 - 5],
                      minWidth: type == 3 ? '202px' : '50px',
                      minHeight: type == 3 ? '176px' : '50px',
                      duration: [200, 200],
                      trigger: 'click',
                      wait: {
                        iconHeight: '40px'
                      },
                      onShow: function () {
                        var _onShow2 = _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee9(_ref20) {
                          var reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps, calendarBlock, currentDate, datePicker, _yield$axiosQuery8, _data, _error, _status, _headers;

                          return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee9$(_context9) {
                            while (1) {
                              switch (_context9.prev = _context9.next) {
                                case 0:
                                  reference = _ref20.reference, popper = _ref20.popper, show = _ref20.show, hide = _ref20.hide, destroy = _ref20.destroy, waitDetroy = _ref20.waitDetroy, setContent = _ref20.setContent, setData = _ref20.setData, setProps = _ref20.setProps;

                                  if (!(type == 3)) {
                                    _context9.next = 11;
                                    break;
                                  }

                                  calendarBlock = '<div ondblclick="event.stopPropagation();">' + '<div><div id="editCellCalendar"></div></div>' + '<div class="mt5px text-end">' + '<div class="button verysmall-button button-light">' + '<button title="Очистить" id="editCellCalendarClear">Очистить</button>' + '</div>' + '</div>' + '</div>';
                                  _context9.next = 5;
                                  return setData(calendarBlock);

                                case 5:
                                  currentDate = $(cell).find('[edittedplace]').attr('date') || false;
                                  datePicker = ddrDatepicker($(popper).find('#editCellCalendar')[0], {
                                    startDay: 1,
                                    defaultView: 'calendar',
                                    overlayPlaceholder: 'Введите год',
                                    customDays: ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'],
                                    customMonths: ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'],
                                    alwaysShow: true,
                                    dateSelected: currentDate ? new Date(currentDate) : new Date(),
                                    onSelect: function () {
                                      var _onSelect = _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee6(_ref21, date) {
                                        var el, destroy, rawDate, toCellText, emptyVal, cellDateWait, _yield$axiosQuery6, data, error, _cellEditTooltip2;

                                        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee6$(_context6) {
                                          while (1) {
                                            switch (_context6.prev = _context6.next) {
                                              case 0:
                                                el = _ref21.el, destroy = _ref21.destroy;
                                                rawDate = date.getFullYear() + '-' + addZero(date.getMonth() + 1) + '-' + addZero(date.getDate()) + ' 00:00:00';
                                                toCellText = addZero(date.getDate()) + '.' + addZero(date.getMonth() + 1) + '.' + date.getFullYear().toString().substr(-2);
                                                emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
                                                cellDateWait = $(reference).ddrWait({
                                                  iconHeight: '30px',
                                                  tag: 'noscroll noopen edittedwait'
                                                });
                                                _context6.next = 7;
                                                return axiosQuery('post', 'site/contracts/cell_edit', {
                                                  contract_id: contractId,
                                                  column: column,
                                                  type: type,
                                                  data: rawDate
                                                }, 'json');

                                              case 7:
                                                _yield$axiosQuery6 = _context6.sent;
                                                data = _yield$axiosQuery6.data;
                                                error = _yield$axiosQuery6.error;

                                                if (error) {
                                                  cellDateWait.off();
                                                  $.notify('Ошибка сохранения ячейки!', 'error');
                                                  console.log(error === null || error === void 0 ? void 0 : error.message, error.errors);
                                                }

                                                if (data) {
                                                  $.notify('Сохранено!');
                                                  $(cell).find('[edittedplace]').setAttrib('date', rawDate);
                                                  $(cell).find('[edittedplace]').text(toCellText || emptyVal);
                                                  cellDateWait.destroy();
                                                  unEditCell(cell);
                                                  (_cellEditTooltip2 = cellEditTooltip) === null || _cellEditTooltip2 === void 0 ? void 0 : _cellEditTooltip2.destroy();
                                                }

                                              case 12:
                                              case "end":
                                                return _context6.stop();
                                            }
                                          }
                                        }, _callee6);
                                      }));

                                      function onSelect(_x3, _x4) {
                                        return _onSelect.apply(this, arguments);
                                      }

                                      return onSelect;
                                    }()
                                  });
                                  $('#editCellCalendarClear').one(tapEvent, /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee7() {
                                    var cellDateWait, emptyVal, _yield$axiosQuery7, data, error, _cellEditTooltip3;

                                    return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee7$(_context7) {
                                      while (1) {
                                        switch (_context7.prev = _context7.next) {
                                          case 0:
                                            cellDateWait = $(reference).ddrWait({
                                              iconHeight: '30px',
                                              tag: 'noscroll noopen edittedwait'
                                            });
                                            emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
                                            _context7.next = 4;
                                            return axiosQuery('post', 'site/contracts/cell_edit', {
                                              contract_id: contractId,
                                              column: column,
                                              type: type,
                                              data: null
                                            }, 'json');

                                          case 4:
                                            _yield$axiosQuery7 = _context7.sent;
                                            data = _yield$axiosQuery7.data;
                                            error = _yield$axiosQuery7.error;

                                            if (error) {
                                              cellDateWait.off();
                                              $.notify('Ошибка сохранения ячейки!', 'error');
                                              console.log(error === null || error === void 0 ? void 0 : error.message, error.errors);
                                            }

                                            if (data) {
                                              $.notify('Сохранено!');
                                              $(cell).find('[edittedplace]').removeAttrib('date');
                                              $(cell).find('[edittedplace]').text(emptyVal);
                                              cellDateWait.destroy();
                                              unEditCell(cell);
                                              (_cellEditTooltip3 = cellEditTooltip) === null || _cellEditTooltip3 === void 0 ? void 0 : _cellEditTooltip3.destroy();
                                            }

                                          case 9:
                                          case "end":
                                            return _context7.stop();
                                        }
                                      }
                                    }, _callee7);
                                  })));
                                  $(datePicker.el).siblings('.qs-datepicker-container').addClass('qs-datepicker-container-noshadow qs-datepicker-container-relative ');
                                  _context9.next = 20;
                                  break;

                                case 11:
                                  _context9.next = 13;
                                  return axiosQuery('get', 'site/contracts/cell_edit', {
                                    contract_id: contractId,
                                    column: column,
                                    type: type
                                  }, 'json');

                                case 13:
                                  _yield$axiosQuery8 = _context9.sent;
                                  _data = _yield$axiosQuery8.data;
                                  _error = _yield$axiosQuery8.error;
                                  _status = _yield$axiosQuery8.status;
                                  _headers = _yield$axiosQuery8.headers;
                                  _context9.next = 20;
                                  return setData(_data);

                                case 20:
                                  waitDetroy();
                                  $('#contractsList').one('scroll', function () {
                                    var _cellEditTooltip4;

                                    // При скролле списка скрыть тултип комментариев
                                    if (((_cellEditTooltip4 = cellEditTooltip) === null || _cellEditTooltip4 === void 0 ? void 0 : _cellEditTooltip4.destroy) != undefined) cellEditTooltip.destroy();
                                  });
                                  $(popper).find('[edittedlistvalue]').one(tapEvent, /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee8() {
                                    var value, emptyVal, _yield$axiosQuery9, savedRes, savedErr, _cellEditTooltip5;

                                    return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee8$(_context8) {
                                      while (1) {
                                        switch (_context8.prev = _context8.next) {
                                          case 0:
                                            cellWait.on();
                                            value = $(this).attr('edittedlistvalue');
                                            emptyVal = $(cell).find('[edittedplace]').attr('edittedplace');
                                            _context8.next = 5;
                                            return axiosQuery('post', 'site/contracts/cell_edit', {
                                              contract_id: contractId,
                                              column: column,
                                              type: type,
                                              data: value
                                            }, 'json');

                                          case 5:
                                            _yield$axiosQuery9 = _context8.sent;
                                            savedRes = _yield$axiosQuery9.data;
                                            savedErr = _yield$axiosQuery9.error;

                                            if (savedErr) {
                                              cellWait.off();
                                              $.notify('Ошибка сохранения ячейки!', 'error');
                                              console.log(savedErr === null || savedErr === void 0 ? void 0 : savedErr.message, savedErr.errors);
                                            }

                                            if (savedRes) {
                                              $.notify('Сохранено!');
                                              $(cell).find('[edittedplace]').text(savedRes || emptyVal);
                                              cellWait.destroy();
                                              unEditCell(cell);
                                              (_cellEditTooltip5 = cellEditTooltip) === null || _cellEditTooltip5 === void 0 ? void 0 : _cellEditTooltip5.destroy();
                                            }

                                          case 10:
                                          case "end":
                                            return _context8.stop();
                                        }
                                      }
                                    }, _callee8, this);
                                  })));

                                case 23:
                                case "end":
                                  return _context9.stop();
                              }
                            }
                          }, _callee9);
                        }));

                        function onShow(_x2) {
                          return _onShow2.apply(this, arguments);
                        }

                        return onShow;
                      }(),
                      onDestroy: function onDestroy() {
                        $(cell).removeAttrib('tooltiped');
                      }
                    });
                  }

                case 25:
                  cellWait.off();

                case 26:
                case "end":
                  return _context10.stop();
              }
            }
          }, _callee10);
        }))();
      }
    }, {
      name: 'Экспорт в Excel',
      //visible: hasCheckbox && isDeptCheckbox,
      sort: 8,
      onClick: function onClick() {
        return _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee12() {
          var contractsIds, _yield$ddrPopup, state, popper, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, query, onScroll, disableButtons, enableButtons, setWidth, _yield$axiosQuery10, data, error, status, headers;

          return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee12$(_context12) {
            while (1) {
              switch (_context12.prev = _context12.next) {
                case 0:
                  contractsIds = selectedContracts.items;
                  _context12.next = 3;
                  return ddrPopup({
                    title: 'Экспорт данных в Excel',
                    // заголовок
                    width: 400,
                    // ширина окна
                    //frameOnly, // Загрузить только каркас
                    //html, // контент
                    //lhtml, // контент из языковых файлов
                    buttons: ['ui.cancel', {
                      title: 'Экспорт',
                      variant: 'blue',
                      action: 'exportContractsData'
                    }] // массив кнопок
                    //buttonsAlign, // выравнивание вправо
                    //disabledButtons, // при старте все кнопки кроме закрытия будут disabled
                    //closeByBackdrop, // Закрывать окно только по кнопкам [ddrpopupclose]
                    //changeWidthAnimationDuration, // ms
                    //buttonsGroup, // группа для кнопок
                    //winClass, // добавить класс к модальному окну
                    //centerMode, // контент по центру
                    //topClose // верхняя кнопка закрыть

                  });

                case 3:
                  _yield$ddrPopup = _context12.sent;
                  state = _yield$ddrPopup.state;
                  popper = _yield$ddrPopup.popper;
                  wait = _yield$ddrPopup.wait;
                  setTitle = _yield$ddrPopup.setTitle;
                  setButtons = _yield$ddrPopup.setButtons;
                  loadData = _yield$ddrPopup.loadData;
                  setHtml = _yield$ddrPopup.setHtml;
                  setLHtml = _yield$ddrPopup.setLHtml;
                  dialog = _yield$ddrPopup.dialog;
                  close = _yield$ddrPopup.close;
                  query = _yield$ddrPopup.query;
                  onScroll = _yield$ddrPopup.onScroll;
                  disableButtons = _yield$ddrPopup.disableButtons;
                  enableButtons = _yield$ddrPopup.enableButtons;
                  setWidth = _yield$ddrPopup.setWidth;
                  _context12.next = 21;
                  return axiosQuery('get', 'site/contracts/to_export', {
                    contracts_ids: contractsIds
                  });

                case 21:
                  _yield$axiosQuery10 = _context12.sent;
                  data = _yield$axiosQuery10.data;
                  error = _yield$axiosQuery10.error;
                  status = _yield$axiosQuery10.status;
                  headers = _yield$axiosQuery10.headers;

                  if (error) {
                    $.notify('Ошибка! Не удалось открыть окно настроек экспорта!', 'error');
                    console.log(error === null || error === void 0 ? void 0 : error.message, error.errors);
                  }

                  _context12.next = 29;
                  return setHtml(data);

                case 29:
                  wait(false);
                  $.exportContractsData = /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee11() {
                    var colums, sort, order, _yield$axiosQuery11, data, error, status, headers, d;

                    return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee11$(_context11) {
                      while (1) {
                        switch (_context11.prev = _context11.next) {
                          case 0:
                            wait();
                            colums = [];
                            $(popper).find('[columtoxeport]:checked').each(function (k, item) {
                              var field = $(item).attr('columtoxeport');
                              colums.push(field);
                            });
                            sort = ddrStore('site-contracts-sortfield') || 'id', order = ddrStore('site-contracts-sortorder') || 'ASC';
                            _context11.next = 6;
                            return axiosQuery('post', 'site/contracts/to_export', {
                              contracts_ids: contractsIds,
                              colums: colums,
                              sort: sort,
                              order: order
                            }, 'blob');

                          case 6:
                            _yield$axiosQuery11 = _context11.sent;
                            data = _yield$axiosQuery11.data;
                            error = _yield$axiosQuery11.error;
                            status = _yield$axiosQuery11.status;
                            headers = _yield$axiosQuery11.headers;

                            if (!(headers['content-type'] != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
                              _context11.next = 15;
                              break;
                            }

                            $.notify('Ошибка экспорта данных', 'error');
                            wait(false);
                            return _context11.abrupt("return");

                          case 15:
                            d = getDateFromString();
                            exportFile({
                              data: data,
                              headers: headers,
                              filename: 'Договоры ' + d.day + ' ' + d.namedMonth + ' ' + d.year + 'г. в ' + d.hours + '-' + d.minutes
                            }, function () {
                              close();
                            });

                          case 17:
                          case "end":
                            return _context11.stop();
                        }
                      }
                    }, _callee11);
                  }));
                  /*commentsTooltip = $(cell).ddrTooltip({
                  	//cls: 'w44rem',
                  	placement: 'bottom',
                  	tag: 'noscroll noopen',
                  	offset: [0 -5],
                  	minWidth: '200px',
                  	minHeight: '200px',
                  	duration: [200, 200],
                  	trigger: 'click',
                  	wait: {
                  		iconHeight: '40px'
                  	},
                  	onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
                  		
                  		const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/cell_comment', {
                  			contract_id: contractId, 
                  			department_id: departmentId,
                  			step_id: stepId,
                  		}, 'json');
                  		
                  		
                  		await setData(data);
                  		
                  		waitDetroy();
                  		
                  		const textarea = $(popper).find('#sendCellComment');
                  		
                  		$(textarea).focus();
                  		
                  		textarea[0].selectionStart = textarea[0].selectionEnd = textarea[0].value.length;
                  		
                  		$('#contractsList').one('scroll', function() {
                  			// При скролле списка скрыть тултип комментариев
                  			if (commentsTooltip?.destroy != undefined) commentsTooltip.destroy();
                  		});
                  		
                  		
                  		let inputCellCommentTOut;
                  		$(textarea).on('input', function() {
                  			clearTimeout(inputCellCommentTOut);
                  			inputCellCommentTOut = setTimeout(async () => {
                  				const comment = $(this).val();
                  				const {data: postRes, error: postErr, status, headers} = await axiosQuery('post', 'site/contracts/cell_comment', {
                  					contract_id: contractId, 
                  					department_id: departmentId,
                  					step_id: stepId,
                  					comment,
                  				}, 'json');
                  				
                  				if (postErr) {
                  					console.log(postErr);
                  					$.notify('Ошибка! Не удалось задать комментарий!', 'error');
                  					return;
                  				}
                  				
                  				if (postRes) {
                  					if (comment) $(reference).append('<div class="trangled trangled-top-right"></div>');
                  					else $(reference).find('.trangled').remove();
                  					
                  					//$.notify('Комментарий успешно сохранен!');
                  					//$(this).ddrInputs('change');
                  				}
                  				
                  			}, 500);
                  		});
                  	},
                  	onDestroy: function() {
                  		//$(cell).removeAttrib('tooltiped');
                  	}
                  });*/

                case 31:
                case "end":
                  return _context12.stop();
              }
            }
          }, _callee12);
        }))();
      }
    }];
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
/* harmony export */   "contextMenu": function() { return /* reexport safe */ _contextMenu_js__WEBPACK_IMPORTED_MODULE_3__.contextMenu; },
/* harmony export */   "showSelections": function() { return /* reexport safe */ _showSelections_js__WEBPACK_IMPORTED_MODULE_2__.showSelections; }
/* harmony export */ });
/* harmony import */ var _calcSubcontracting_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./calcSubcontracting.js */ "./resources/js/sections/site/contracts/calcSubcontracting.js");
/* harmony import */ var _calcGencontracting_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./calcGencontracting.js */ "./resources/js/sections/site/contracts/calcGencontracting.js");
/* harmony import */ var _showSelections_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./showSelections.js */ "./resources/js/sections/site/contracts/showSelections.js");
/* harmony import */ var _contextMenu_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./contextMenu.js */ "./resources/js/sections/site/contracts/contextMenu.js");





/***/ }),

/***/ "./resources/js/sections/site/contracts/showSelections.js":
/*!****************************************************************!*\
  !*** ./resources/js/sections/site/contracts/showSelections.js ***!
  \****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "showSelections": function() { return /* binding */ showSelections; }
/* harmony export */ });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);


function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function showSelections(cell) {
  var contractId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  var selectionsTooltip = arguments.length > 2 ? arguments[2] : undefined;
  if (_.isNull(contractId)) return false;
  selectionsTooltip = $(cell).ddrTooltip({
    //cls: 'w44rem',
    placement: 'right-start',
    tag: 'noscroll noopen',
    offset: [0, 0],
    minWidth: '200px',
    minHeight: '40px',
    duration: [200, 200],
    trigger: 'mouseenter focus',
    wait: {
      iconHeight: '40px'
    },
    onShow: function () {
      var _onShow = _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee(_ref) {
        var reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps, _yield$axiosQuery, data, error, status, headers;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                reference = _ref.reference, popper = _ref.popper, show = _ref.show, hide = _ref.hide, destroy = _ref.destroy, waitDetroy = _ref.waitDetroy, setContent = _ref.setContent, setData = _ref.setData, setProps = _ref.setProps;
                _context.next = 3;
                return axiosQuery('get', 'site/contracts/contract_selections', {
                  contract_id: contractId
                }, 'json');

              case 3:
                _yield$axiosQuery = _context.sent;
                data = _yield$axiosQuery.data;
                error = _yield$axiosQuery.error;
                status = _yield$axiosQuery.status;
                headers = _yield$axiosQuery.headers;

                if (!error) {
                  _context.next = 13;
                  break;
                }

                $.notify('Ошибка! Не удалось загрузить список подборок!', 'error');
                console.log(error.message);
                waitDetroy();
                return _context.abrupt("return");

              case 13:
                _context.next = 15;
                return setData(data);

              case 15:
                waitDetroy();

              case 16:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }));

      function onShow(_x) {
        return _onShow.apply(this, arguments);
      }

      return onShow;
    }()
  }); // закрытие окна по нажатию клавиши ESC

  $(document).one('keydown', function (e) {
    var _selectionsTooltip;

    if (e.keyCode == 27 && ((_selectionsTooltip = selectionsTooltip) === null || _selectionsTooltip === void 0 ? void 0 : _selectionsTooltip.destroy) != undefined) {
      selectionsTooltip.destroy();
    }
  });
  return selectionsTooltip;
}

/***/ }),

/***/ "./node_modules/regenerator-runtime/runtime.js":
/*!*****************************************************!*\
  !*** ./node_modules/regenerator-runtime/runtime.js ***!
  \*****************************************************/
/***/ (function(module) {

/**
 * Copyright (c) 2014-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var runtime = (function (exports) {
  "use strict";

  var Op = Object.prototype;
  var hasOwn = Op.hasOwnProperty;
  var undefined; // More compressible than void 0.
  var $Symbol = typeof Symbol === "function" ? Symbol : {};
  var iteratorSymbol = $Symbol.iterator || "@@iterator";
  var asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator";
  var toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";

  function define(obj, key, value) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
    return obj[key];
  }
  try {
    // IE 8 has a broken Object.defineProperty that only works on DOM objects.
    define({}, "");
  } catch (err) {
    define = function(obj, key, value) {
      return obj[key] = value;
    };
  }

  function wrap(innerFn, outerFn, self, tryLocsList) {
    // If outerFn provided and outerFn.prototype is a Generator, then outerFn.prototype instanceof Generator.
    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator;
    var generator = Object.create(protoGenerator.prototype);
    var context = new Context(tryLocsList || []);

    // The ._invoke method unifies the implementations of the .next,
    // .throw, and .return methods.
    generator._invoke = makeInvokeMethod(innerFn, self, context);

    return generator;
  }
  exports.wrap = wrap;

  // Try/catch helper to minimize deoptimizations. Returns a completion
  // record like context.tryEntries[i].completion. This interface could
  // have been (and was previously) designed to take a closure to be
  // invoked without arguments, but in all the cases we care about we
  // already have an existing method we want to call, so there's no need
  // to create a new function object. We can even get away with assuming
  // the method takes exactly one argument, since that happens to be true
  // in every case, so we don't have to touch the arguments object. The
  // only additional allocation required is the completion record, which
  // has a stable shape and so hopefully should be cheap to allocate.
  function tryCatch(fn, obj, arg) {
    try {
      return { type: "normal", arg: fn.call(obj, arg) };
    } catch (err) {
      return { type: "throw", arg: err };
    }
  }

  var GenStateSuspendedStart = "suspendedStart";
  var GenStateSuspendedYield = "suspendedYield";
  var GenStateExecuting = "executing";
  var GenStateCompleted = "completed";

  // Returning this object from the innerFn has the same effect as
  // breaking out of the dispatch switch statement.
  var ContinueSentinel = {};

  // Dummy constructor functions that we use as the .constructor and
  // .constructor.prototype properties for functions that return Generator
  // objects. For full spec compliance, you may wish to configure your
  // minifier not to mangle the names of these two functions.
  function Generator() {}
  function GeneratorFunction() {}
  function GeneratorFunctionPrototype() {}

  // This is a polyfill for %IteratorPrototype% for environments that
  // don't natively support it.
  var IteratorPrototype = {};
  define(IteratorPrototype, iteratorSymbol, function () {
    return this;
  });

  var getProto = Object.getPrototypeOf;
  var NativeIteratorPrototype = getProto && getProto(getProto(values([])));
  if (NativeIteratorPrototype &&
      NativeIteratorPrototype !== Op &&
      hasOwn.call(NativeIteratorPrototype, iteratorSymbol)) {
    // This environment has a native %IteratorPrototype%; use it instead
    // of the polyfill.
    IteratorPrototype = NativeIteratorPrototype;
  }

  var Gp = GeneratorFunctionPrototype.prototype =
    Generator.prototype = Object.create(IteratorPrototype);
  GeneratorFunction.prototype = GeneratorFunctionPrototype;
  define(Gp, "constructor", GeneratorFunctionPrototype);
  define(GeneratorFunctionPrototype, "constructor", GeneratorFunction);
  GeneratorFunction.displayName = define(
    GeneratorFunctionPrototype,
    toStringTagSymbol,
    "GeneratorFunction"
  );

  // Helper for defining the .next, .throw, and .return methods of the
  // Iterator interface in terms of a single ._invoke method.
  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function(method) {
      define(prototype, method, function(arg) {
        return this._invoke(method, arg);
      });
    });
  }

  exports.isGeneratorFunction = function(genFun) {
    var ctor = typeof genFun === "function" && genFun.constructor;
    return ctor
      ? ctor === GeneratorFunction ||
        // For the native GeneratorFunction constructor, the best we can
        // do is to check its .name property.
        (ctor.displayName || ctor.name) === "GeneratorFunction"
      : false;
  };

  exports.mark = function(genFun) {
    if (Object.setPrototypeOf) {
      Object.setPrototypeOf(genFun, GeneratorFunctionPrototype);
    } else {
      genFun.__proto__ = GeneratorFunctionPrototype;
      define(genFun, toStringTagSymbol, "GeneratorFunction");
    }
    genFun.prototype = Object.create(Gp);
    return genFun;
  };

  // Within the body of any async function, `await x` is transformed to
  // `yield regeneratorRuntime.awrap(x)`, so that the runtime can test
  // `hasOwn.call(value, "__await")` to determine if the yielded value is
  // meant to be awaited.
  exports.awrap = function(arg) {
    return { __await: arg };
  };

  function AsyncIterator(generator, PromiseImpl) {
    function invoke(method, arg, resolve, reject) {
      var record = tryCatch(generator[method], generator, arg);
      if (record.type === "throw") {
        reject(record.arg);
      } else {
        var result = record.arg;
        var value = result.value;
        if (value &&
            typeof value === "object" &&
            hasOwn.call(value, "__await")) {
          return PromiseImpl.resolve(value.__await).then(function(value) {
            invoke("next", value, resolve, reject);
          }, function(err) {
            invoke("throw", err, resolve, reject);
          });
        }

        return PromiseImpl.resolve(value).then(function(unwrapped) {
          // When a yielded Promise is resolved, its final value becomes
          // the .value of the Promise<{value,done}> result for the
          // current iteration.
          result.value = unwrapped;
          resolve(result);
        }, function(error) {
          // If a rejected Promise was yielded, throw the rejection back
          // into the async generator function so it can be handled there.
          return invoke("throw", error, resolve, reject);
        });
      }
    }

    var previousPromise;

    function enqueue(method, arg) {
      function callInvokeWithMethodAndArg() {
        return new PromiseImpl(function(resolve, reject) {
          invoke(method, arg, resolve, reject);
        });
      }

      return previousPromise =
        // If enqueue has been called before, then we want to wait until
        // all previous Promises have been resolved before calling invoke,
        // so that results are always delivered in the correct order. If
        // enqueue has not been called before, then it is important to
        // call invoke immediately, without waiting on a callback to fire,
        // so that the async generator function has the opportunity to do
        // any necessary setup in a predictable way. This predictability
        // is why the Promise constructor synchronously invokes its
        // executor callback, and why async functions synchronously
        // execute code before the first await. Since we implement simple
        // async functions in terms of async generators, it is especially
        // important to get this right, even though it requires care.
        previousPromise ? previousPromise.then(
          callInvokeWithMethodAndArg,
          // Avoid propagating failures to Promises returned by later
          // invocations of the iterator.
          callInvokeWithMethodAndArg
        ) : callInvokeWithMethodAndArg();
    }

    // Define the unified helper method that is used to implement .next,
    // .throw, and .return (see defineIteratorMethods).
    this._invoke = enqueue;
  }

  defineIteratorMethods(AsyncIterator.prototype);
  define(AsyncIterator.prototype, asyncIteratorSymbol, function () {
    return this;
  });
  exports.AsyncIterator = AsyncIterator;

  // Note that simple async functions are implemented on top of
  // AsyncIterator objects; they just return a Promise for the value of
  // the final result produced by the iterator.
  exports.async = function(innerFn, outerFn, self, tryLocsList, PromiseImpl) {
    if (PromiseImpl === void 0) PromiseImpl = Promise;

    var iter = new AsyncIterator(
      wrap(innerFn, outerFn, self, tryLocsList),
      PromiseImpl
    );

    return exports.isGeneratorFunction(outerFn)
      ? iter // If outerFn is a generator, return the full iterator.
      : iter.next().then(function(result) {
          return result.done ? result.value : iter.next();
        });
  };

  function makeInvokeMethod(innerFn, self, context) {
    var state = GenStateSuspendedStart;

    return function invoke(method, arg) {
      if (state === GenStateExecuting) {
        throw new Error("Generator is already running");
      }

      if (state === GenStateCompleted) {
        if (method === "throw") {
          throw arg;
        }

        // Be forgiving, per 25.3.3.3.3 of the spec:
        // https://people.mozilla.org/~jorendorff/es6-draft.html#sec-generatorresume
        return doneResult();
      }

      context.method = method;
      context.arg = arg;

      while (true) {
        var delegate = context.delegate;
        if (delegate) {
          var delegateResult = maybeInvokeDelegate(delegate, context);
          if (delegateResult) {
            if (delegateResult === ContinueSentinel) continue;
            return delegateResult;
          }
        }

        if (context.method === "next") {
          // Setting context._sent for legacy support of Babel's
          // function.sent implementation.
          context.sent = context._sent = context.arg;

        } else if (context.method === "throw") {
          if (state === GenStateSuspendedStart) {
            state = GenStateCompleted;
            throw context.arg;
          }

          context.dispatchException(context.arg);

        } else if (context.method === "return") {
          context.abrupt("return", context.arg);
        }

        state = GenStateExecuting;

        var record = tryCatch(innerFn, self, context);
        if (record.type === "normal") {
          // If an exception is thrown from innerFn, we leave state ===
          // GenStateExecuting and loop back for another invocation.
          state = context.done
            ? GenStateCompleted
            : GenStateSuspendedYield;

          if (record.arg === ContinueSentinel) {
            continue;
          }

          return {
            value: record.arg,
            done: context.done
          };

        } else if (record.type === "throw") {
          state = GenStateCompleted;
          // Dispatch the exception by looping back around to the
          // context.dispatchException(context.arg) call above.
          context.method = "throw";
          context.arg = record.arg;
        }
      }
    };
  }

  // Call delegate.iterator[context.method](context.arg) and handle the
  // result, either by returning a { value, done } result from the
  // delegate iterator, or by modifying context.method and context.arg,
  // setting context.delegate to null, and returning the ContinueSentinel.
  function maybeInvokeDelegate(delegate, context) {
    var method = delegate.iterator[context.method];
    if (method === undefined) {
      // A .throw or .return when the delegate iterator has no .throw
      // method always terminates the yield* loop.
      context.delegate = null;

      if (context.method === "throw") {
        // Note: ["return"] must be used for ES3 parsing compatibility.
        if (delegate.iterator["return"]) {
          // If the delegate iterator has a return method, give it a
          // chance to clean up.
          context.method = "return";
          context.arg = undefined;
          maybeInvokeDelegate(delegate, context);

          if (context.method === "throw") {
            // If maybeInvokeDelegate(context) changed context.method from
            // "return" to "throw", let that override the TypeError below.
            return ContinueSentinel;
          }
        }

        context.method = "throw";
        context.arg = new TypeError(
          "The iterator does not provide a 'throw' method");
      }

      return ContinueSentinel;
    }

    var record = tryCatch(method, delegate.iterator, context.arg);

    if (record.type === "throw") {
      context.method = "throw";
      context.arg = record.arg;
      context.delegate = null;
      return ContinueSentinel;
    }

    var info = record.arg;

    if (! info) {
      context.method = "throw";
      context.arg = new TypeError("iterator result is not an object");
      context.delegate = null;
      return ContinueSentinel;
    }

    if (info.done) {
      // Assign the result of the finished delegate to the temporary
      // variable specified by delegate.resultName (see delegateYield).
      context[delegate.resultName] = info.value;

      // Resume execution at the desired location (see delegateYield).
      context.next = delegate.nextLoc;

      // If context.method was "throw" but the delegate handled the
      // exception, let the outer generator proceed normally. If
      // context.method was "next", forget context.arg since it has been
      // "consumed" by the delegate iterator. If context.method was
      // "return", allow the original .return call to continue in the
      // outer generator.
      if (context.method !== "return") {
        context.method = "next";
        context.arg = undefined;
      }

    } else {
      // Re-yield the result returned by the delegate method.
      return info;
    }

    // The delegate iterator is finished, so forget it and continue with
    // the outer generator.
    context.delegate = null;
    return ContinueSentinel;
  }

  // Define Generator.prototype.{next,throw,return} in terms of the
  // unified ._invoke helper method.
  defineIteratorMethods(Gp);

  define(Gp, toStringTagSymbol, "Generator");

  // A Generator should always return itself as the iterator object when the
  // @@iterator function is called on it. Some browsers' implementations of the
  // iterator prototype chain incorrectly implement this, causing the Generator
  // object to not be returned from this call. This ensures that doesn't happen.
  // See https://github.com/facebook/regenerator/issues/274 for more details.
  define(Gp, iteratorSymbol, function() {
    return this;
  });

  define(Gp, "toString", function() {
    return "[object Generator]";
  });

  function pushTryEntry(locs) {
    var entry = { tryLoc: locs[0] };

    if (1 in locs) {
      entry.catchLoc = locs[1];
    }

    if (2 in locs) {
      entry.finallyLoc = locs[2];
      entry.afterLoc = locs[3];
    }

    this.tryEntries.push(entry);
  }

  function resetTryEntry(entry) {
    var record = entry.completion || {};
    record.type = "normal";
    delete record.arg;
    entry.completion = record;
  }

  function Context(tryLocsList) {
    // The root entry object (effectively a try statement without a catch
    // or a finally block) gives us a place to store values thrown from
    // locations where there is no enclosing try statement.
    this.tryEntries = [{ tryLoc: "root" }];
    tryLocsList.forEach(pushTryEntry, this);
    this.reset(true);
  }

  exports.keys = function(object) {
    var keys = [];
    for (var key in object) {
      keys.push(key);
    }
    keys.reverse();

    // Rather than returning an object with a next method, we keep
    // things simple and return the next function itself.
    return function next() {
      while (keys.length) {
        var key = keys.pop();
        if (key in object) {
          next.value = key;
          next.done = false;
          return next;
        }
      }

      // To avoid creating an additional object, we just hang the .value
      // and .done properties off the next function object itself. This
      // also ensures that the minifier will not anonymize the function.
      next.done = true;
      return next;
    };
  };

  function values(iterable) {
    if (iterable) {
      var iteratorMethod = iterable[iteratorSymbol];
      if (iteratorMethod) {
        return iteratorMethod.call(iterable);
      }

      if (typeof iterable.next === "function") {
        return iterable;
      }

      if (!isNaN(iterable.length)) {
        var i = -1, next = function next() {
          while (++i < iterable.length) {
            if (hasOwn.call(iterable, i)) {
              next.value = iterable[i];
              next.done = false;
              return next;
            }
          }

          next.value = undefined;
          next.done = true;

          return next;
        };

        return next.next = next;
      }
    }

    // Return an iterator with no values.
    return { next: doneResult };
  }
  exports.values = values;

  function doneResult() {
    return { value: undefined, done: true };
  }

  Context.prototype = {
    constructor: Context,

    reset: function(skipTempReset) {
      this.prev = 0;
      this.next = 0;
      // Resetting context._sent for legacy support of Babel's
      // function.sent implementation.
      this.sent = this._sent = undefined;
      this.done = false;
      this.delegate = null;

      this.method = "next";
      this.arg = undefined;

      this.tryEntries.forEach(resetTryEntry);

      if (!skipTempReset) {
        for (var name in this) {
          // Not sure about the optimal order of these conditions:
          if (name.charAt(0) === "t" &&
              hasOwn.call(this, name) &&
              !isNaN(+name.slice(1))) {
            this[name] = undefined;
          }
        }
      }
    },

    stop: function() {
      this.done = true;

      var rootEntry = this.tryEntries[0];
      var rootRecord = rootEntry.completion;
      if (rootRecord.type === "throw") {
        throw rootRecord.arg;
      }

      return this.rval;
    },

    dispatchException: function(exception) {
      if (this.done) {
        throw exception;
      }

      var context = this;
      function handle(loc, caught) {
        record.type = "throw";
        record.arg = exception;
        context.next = loc;

        if (caught) {
          // If the dispatched exception was caught by a catch block,
          // then let that catch block handle the exception normally.
          context.method = "next";
          context.arg = undefined;
        }

        return !! caught;
      }

      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        var record = entry.completion;

        if (entry.tryLoc === "root") {
          // Exception thrown outside of any try block that could handle
          // it, so set the completion value of the entire function to
          // throw the exception.
          return handle("end");
        }

        if (entry.tryLoc <= this.prev) {
          var hasCatch = hasOwn.call(entry, "catchLoc");
          var hasFinally = hasOwn.call(entry, "finallyLoc");

          if (hasCatch && hasFinally) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            } else if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }

          } else if (hasCatch) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            }

          } else if (hasFinally) {
            if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }

          } else {
            throw new Error("try statement without catch or finally");
          }
        }
      }
    },

    abrupt: function(type, arg) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc <= this.prev &&
            hasOwn.call(entry, "finallyLoc") &&
            this.prev < entry.finallyLoc) {
          var finallyEntry = entry;
          break;
        }
      }

      if (finallyEntry &&
          (type === "break" ||
           type === "continue") &&
          finallyEntry.tryLoc <= arg &&
          arg <= finallyEntry.finallyLoc) {
        // Ignore the finally entry if control is not jumping to a
        // location outside the try/catch block.
        finallyEntry = null;
      }

      var record = finallyEntry ? finallyEntry.completion : {};
      record.type = type;
      record.arg = arg;

      if (finallyEntry) {
        this.method = "next";
        this.next = finallyEntry.finallyLoc;
        return ContinueSentinel;
      }

      return this.complete(record);
    },

    complete: function(record, afterLoc) {
      if (record.type === "throw") {
        throw record.arg;
      }

      if (record.type === "break" ||
          record.type === "continue") {
        this.next = record.arg;
      } else if (record.type === "return") {
        this.rval = this.arg = record.arg;
        this.method = "return";
        this.next = "end";
      } else if (record.type === "normal" && afterLoc) {
        this.next = afterLoc;
      }

      return ContinueSentinel;
    },

    finish: function(finallyLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.finallyLoc === finallyLoc) {
          this.complete(entry.completion, entry.afterLoc);
          resetTryEntry(entry);
          return ContinueSentinel;
        }
      }
    },

    "catch": function(tryLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc === tryLoc) {
          var record = entry.completion;
          if (record.type === "throw") {
            var thrown = record.arg;
            resetTryEntry(entry);
          }
          return thrown;
        }
      }

      // The context.catch method must only be called with a location
      // argument that corresponds to a known catch block.
      throw new Error("illegal catch attempt");
    },

    delegateYield: function(iterable, resultName, nextLoc) {
      this.delegate = {
        iterator: values(iterable),
        resultName: resultName,
        nextLoc: nextLoc
      };

      if (this.method === "next") {
        // Deliberately forget the last sent value so that we don't
        // accidentally pass it on to the delegate.
        this.arg = undefined;
      }

      return ContinueSentinel;
    }
  };

  // Regardless of whether this script is executing as a CommonJS module
  // or not, return the runtime object so that we can declare the variable
  // regeneratorRuntime in the outer scope, which allows this module to be
  // injected easily by `bin/regenerator --include-runtime script.js`.
  return exports;

}(
  // If this script is executing as a CommonJS module, use module.exports
  // as the regeneratorRuntime namespace. Otherwise create a new empty
  // object. Either way, the resulting object will be used to initialize
  // the regeneratorRuntime variable at the top of this file.
   true ? module.exports : 0
));

try {
  regeneratorRuntime = runtime;
} catch (accidentalStrictMode) {
  // This module should not be running in strict mode, so the above
  // assignment should always work unless something is misconfigured. Just
  // in case runtime.js accidentally runs in strict mode, in modern engines
  // we can explicitly access globalThis. In older engines we can escape
  // strict mode using a global Function call. This could conceivably fail
  // if a Content Security Policy forbids using Function, but in that case
  // the proper solution is to fix the accidental strict mode problem. If
  // you've misconfigured your bundler to force strict mode and applied a
  // CSP to forbid Function, and you're not willing to fix either of those
  // problems, please detail your unique predicament in a GitHub issue.
  if (typeof globalThis === "object") {
    globalThis.regeneratorRuntime = runtime;
  } else {
    Function("r", "regeneratorRuntime = r")(runtime);
  }
}


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
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
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