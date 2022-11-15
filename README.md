# Fork

The purpose of this fork is to introduce javascript support to question to allow for custom rendering of controls to implement features that don't exist within the main branch - like randomising the answer order, or pre-populating fields based on external data.

For example, this code adds a range slider to the text control (using noUiSlider):
```js
require(['https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.1/nouislider.min.js'], function(noUiSlider) {

    let field = document.querySelector("#qn-{{id}}").querySelector("input[type='text']");

    let wrapper = document.createElement('div');
    wrapper.classList.add('range-percent-picker');
    field.insertAdjacentElement('beforebegin', wrapper);

    let node = document.createElement('div');
    node.id = "qn-{{id}}-slider";
    wrapper.appendChild(node);

    const classes = ['pale-blue-bg', 'lime-bg', 'yellow-bg']; // one more than the length of values

    let input = (field.value.length ? field.value : '30%,40%,30%').split(',').map(function(v) { return parseInt(v); });
    let values = [input[0],input[1]]; // last value is implied

    let slider = document.getElementById(node.id);

    noUiSlider.create(slider, {
        start: values,
        connect: [true,true,true],
        range: {
            'min': [0],
            'max': [100]
        },
        step: 1
    });

    slider.noUiSlider.on('update', function (values, handle, unencoded, tap, positions, noUiSlider) {
        values.push(100 - values[0] - values[1]); // 3rd value is implied
        field.value = values.map(function(v) { return parseInt(v,10) + '%';}).join(',');
    });

    let connect = slider.querySelectorAll('.noUi-connect');
    for (var i = 0; i < connect.length; i++) {
        connect[i].classList.add(classes[i]);
    }
});
```

This code randomises the order of the answers (except the last one, which is where you might have an 'Other answer' type response, and this should be at the bottom).

```js
function fy(a,b,c,d){c=a.length;while(c)b=Math.random()*c--|0,d=a[c],a[c]=a[b],a[b]=d}
for (node of document.querySelector("#qn-{{id}} .qn-answer").childNodes) if (node.nodeType === Node.COMMENT_NODE) node.parentNode.removeChild(node);

let ar = [], tmp = [];
let pool = document.querySelector('#qn-{{id}} .qn-answer');
for (child of pool.children) {
    if (child === pool.lastElementChild || child.nodeName === 'BR') {
        ar.push(tmp.join(''));tmp = [];
    } else {
        tmp.push(child.outerHTML);
    }
}

let last = ar.pop();
fy(ar);
ar.push(last);

pool.innerHTML = ar.join('<br />');
```

Javascript has the following fields that can be included in the script, replaced at runtime:

`{{id}}` - The question id (integer)
`{{name}}` - The question name (string)
`{{courseid}}` - The course id (integer)
`{{userid}}` - The user id (integer)
`{{cmid}}` - The module instance number (integer)
`{{sesskey}}` - The session key, useful for ajax scripts

> To support more variables, modify `questionnaire_question_javascript()` in `lib.php`

# Original

The questionnaire module allows you to construct questionnaires (surveys) from a
variety of question type. It was originally based on phpESP, and Open Source
survey tool.

--------------------------------------------------------------------------------
To Install:

1. Load the questionnaire module directory into your "mod" subdirectory.
2. Visit your admin page to create all of the necessary data tables.

--------------------------------------------------------------------------------
To Upgrade:

1. Copy all of the files into your 'mod/questionnaire' directory.
2. Visit your admin page. The database will be updated.

--------------------------------------------------------------------------------
Please read the CHANGES.md file for more info about successive changes

--------------------------------------------------------------------------------
Developers Note:

Do not use the main branch. Questionnaire is maintained in MOODLE_XX_STABLE
branches. Use the latest STABLE branch for development or installation.
