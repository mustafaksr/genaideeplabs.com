/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./js/gemini.js":
/*!**********************!*\
  !*** ./js/gemini.js ***!
  \**********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var marked__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! marked */ \"marked\");\n/* harmony import */ var marked__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(marked__WEBPACK_IMPORTED_MODULE_0__);\n\n\ndocument.addEventListener('DOMContentLoaded', function() {\n    const form = document.getElementById('geminiForm');\n    form.addEventListener('submit', function(event) {\n        event.preventDefault();\n        const prompt = document.getElementById('prompt').value;\n        fetchGeminiResponse(prompt);\n    });\n\n    const copyButton = document.getElementById('copyButton');\n    copyButton.addEventListener('click', function(event) {\n        event.preventDefault(); // Prevent any default action\n        const responseText = document.getElementById('hiddenMarkdown').innerText;\n        console.log('Copy button clicked. Text to copy:', responseText); // Debugging output\n        window.focus();\n        copyToClipboard(responseText);\n    });\n\n    function fetchGeminiResponse(prompt) {\n        const data = {\n            \"prompt\": prompt\n        };\n\n        fetch(`https://backendgemini.genaideeplabs.com/generate-content`, {\n            method: 'POST',\n            headers: {\n                'Content-Type': 'application/json'\n            },\n            body: JSON.stringify(data)\n        })\n        .then(response => response.json())\n        .then(data => {\n            const responseTextArea = document.getElementById('responsePrompt');\n            const markdownContainer = document.getElementById('hiddenMarkdown');\n            const text = data.candidates[0].content.parts[0].text;\n            responseTextArea.value = '';\n            printTextSlowly(responseTextArea, markdownContainer, text);\n            updateHistoryColumn(prompt, text); // Update history column with prompt and response\n        })\n        .catch(error => console.error('Error:', error));\n    }\n\n    function printTextSlowly(textArea, markdownContainer, text) {\n        let index = 0;\n        textArea.value = '';\n        markdownContainer.innerText = ''; // Clear previous markdown\n        const printNextCharacter = () => {\n            if (index < text.length) {\n                textArea.value += text.charAt(index);\n                markdownContainer.innerText += text.charAt(index); // Append to markdown\n                index++;\n                setTimeout(printNextCharacter, 3); // Adjust delay here (milliseconds)\n            } else {\n                // Convert the full markdown to HTML using marked and display it\n                const htmlContent = (0,marked__WEBPACK_IMPORTED_MODULE_0__.marked)(markdownContainer.innerText);\n                document.getElementById('responseContainer').innerHTML = htmlContent;\n            }\n        };\n        printNextCharacter();\n    }\n\n    function updateHistoryColumn(prompt, response) {\n        const historyColumn = document.getElementById('history-column');\n        const historyItem = document.createElement('div');\n        historyItem.textContent = `Prompt: ${prompt}`;\n        const dict_history = new Map();\n        dict_history.set(prompt, response);\n\n        // Adding styles\n        historyItem.style.border = '1px solid #000'; // Change #000 to your desired border color\n        historyItem.style.borderRadius = '8px'; // Change 5px to your desired border radius\n        historyItem.style.padding = '3px'; // Change 10px to your desired padding\n        historyItem.style.cursor = 'pointer'; // Change cursor to pointer to indicate clickability\n\n        // Adding hover effect\n        historyItem.style.transition = 'background-color 0.3s'; // Smooth transition\n        historyItem.addEventListener('mouseover', function() {\n            historyItem.style.backgroundColor = '#022e75'; // Change background color on hover\n        });\n        historyItem.addEventListener('mouseout', function() {\n            historyItem.style.backgroundColor = ''; // Revert back to original background color\n        });\n\n        historyItem.addEventListener('click', function() {\n            // Retrieve response from dict_history using prompt as key\n            const response = dict_history.get(prompt);\n            // Update response prompt element\n            document.getElementById('responsePrompt').value = response;\n            // Update prompt element\n            const promptInput = document.getElementById('prompt');\n            promptInput.value = prompt;\n        });\n\n        // Insert the new history item at the top of the history column\n        if (historyColumn.firstChild) {\n            historyColumn.insertBefore(historyItem, historyColumn.firstChild);\n        } else {\n            historyColumn.appendChild(historyItem);\n        }\n    }\n\n    function copyToClipboard(text) {\n        window.focus(); // Ensure the window is focused\n        navigator.clipboard.writeText(text).then(function() {\n            console.log('Copied to clipboard:', text); // Debugging output\n            alert('Copied to clipboard!');\n        }, function(err) {\n            console.error('Could not copy text: ', err);\n        });\n    }\n});\n\n\n//# sourceURL=webpack://blockify/./js/gemini.js?");

/***/ }),

/***/ "marked":
/*!*************************!*\
  !*** external "marked" ***!
  \*************************/
/***/ ((module) => {

module.exports = marked;

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
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./js/gemini.js");
/******/ 	
/******/ })()
;