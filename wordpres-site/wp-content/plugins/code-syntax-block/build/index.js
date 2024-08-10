(()=>{"use strict";const e=window.wp.hooks,t=window.wp.element,l=(window.wp.apiFetch,window.wp.blockEditor),a=window.wp.components,n=window.wp.i18n,o=({attributes:e,setAttributes:o})=>{const{colorScheme:c,content:r,language:s,lineNumbers:i,title:u}=e;(0,t.useEffect)((()=>{!s&&mkaz_code_syntax_default_lang&&o({language:mkaz_code_syntax_default_lang})}),[s]);const m=i?{"line-numbers":"true"}:{},g=(0,l.useBlockProps)({...m});return(0,t.createElement)(t.Fragment,null,(0,t.createElement)(l.InspectorControls,{key:"controls"},(0,t.createElement)(a.PanelBody,{title:(0,n.__)("Settings")},(0,t.createElement)(a.PanelRow,null,(0,t.createElement)(a.SelectControl,{label:(0,n.__)("Language"),value:s,options:[{label:(0,n.__)("Select code language","code-syntax-block"),value:""}].concat(Object.keys(mkaz_code_syntax_languages).map((e=>({label:mkaz_code_syntax_languages[e],value:e})))),onChange:e=>o({language:e})})),(0,t.createElement)(a.PanelRow,null,(0,t.createElement)(a.ToggleControl,{label:(0,n.__)("Show line numbers"),checked:i,onChange:e=>o({lineNumbers:e})})),(0,t.createElement)(a.PanelRow,null,(0,t.createElement)(a.TextControl,{label:(0,n.__)("Title for Code Block","code-syntax-block"),value:u,onChange:e=>o({title:e}),placeholder:(0,n.__)("Title or File (optional)","code-syntax-block")}))),(0,t.createElement)(a.PanelBody,{title:(0,n.__)("Global Color Scheme","code-syntax-block")},(0,t.createElement)("p",null,"The global color scheme is now set in the Dashboard settings menu."))),(0,t.createElement)(t.Fragment,null,(0,t.createElement)("div",{className:"wp-block mkaz-code-syntax-block__labels"},(0,t.createElement)("span",{className:"mkaz-code-syntax-block__label-lang"},"<",mkaz_code_syntax_languages[s],">"),i&&(0,t.createElement)("span",{className:"mkaz-code-syntax-block__label-line-num"},"#")),(0,t.createElement)("pre",g,(0,t.createElement)(l.RichText,{value:r||"",onChange:e=>o({content:e}),placeholder:(0,n.__)("Write code…"),"aria-label":(0,n.__)("Code"),preserveWhiteSpace:!0,allowedFormats:[],withoutInteractiveFormatting:!0,__unstablePastePlainText:!0}))))},c=({attributes:e})=>{let a="";a=e.language?"language-"+e.language:"",a=e.lineNumbers?a+" line-numbers":a;const n=l.useBlockProps.save({title:e.title});return(0,t.createElement)(t.Fragment,null,(0,t.createElement)("pre",n,(0,t.createElement)(l.RichText.Content,{tagName:"code",value:"string"==typeof e.content?e.content:e.content.toHTMLString({preserveWhiteSpace:!0}),lang:e.language,className:a})))};(0,e.addFilter)("blocks.registerBlockType","mkaz/code-syntax-block",(e=>"core/code"!==e.name?e:{...e,attributes:{...e.attributes,language:{type:"string",selector:"code",source:"attribute",attribute:"lang"},lineNumbers:{type:"boolean"},title:{type:"string",source:"attribute",selector:"pre",attribute:"title"}},edit:o,save:c}))})();