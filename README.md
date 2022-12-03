# Fork

The purpose of this fork is to INTRODUCE new features.

## HTML rendering fixes

* The RATE question type has invalid table markup (unclosed tags). Fixed.
* The RATE question now uses a `<thead>` section for the column labels
* The RADIO (vertical) option now groups label+input inside `<div role='grouping'>` blocks instead of using `<BR />` to separate lines
* Any other html control that groups a label+input (e.g. yes/no) is now wrapped in a `<span role='grouping'>`

## Flow Control

* You can bypass the submission screen altogether. Ending a survey will then either redirect to the Feedback page (if set) OR to the course home.
* When your users get stuck on that submission error screen because they refreshed at the wrong time, offer them a way out (button).

## Question Type changes

* a RATE question has a new sub-type of `Table (checkboxes)`.

> Rate ranking is a work-in-progress, for now skipped

## Per-Question Javascript

Javascript can be set per-question to allow for custom rendering of controls to implement features that don't exist within the normal question rendering - like randomising the answer order, or pre-populating fields based on external data (ie. ajax lookups).

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
const shuffleButt = document.querySelector("#qn-{{id}} .qn-answer");

// remove any comment nodes
for (node of shuffleButt.childNodes) if (node.nodeType === Node.COMMENT_NODE) node.parentNode.removeChild(node);

// move the last child into a temporary dom
const fragment = new DocumentFragment();
const other = shuffleButt.children[shuffleButt.children.length-1];
fragment.appendChild(other);

// shuffle the remaining children (fisher yates method)
for (var i = shuffleButt.children.length; i >= 0; i--) {
    shuffleButt.appendChild(shuffleButt.children[Math.random() * i | 0]);
}

// re-append the 'other' node at the bottom
shuffleButt.appendChild(other);
```

Javascript has the following fields that can be included in the script, replaced at runtime:

* `{{id}}` - The question id (integer)
* `{{name}}` - The question name (string)
* `{{courseid}}` - The course id (integer)
* `{{userid}}` - The user id (integer)
* `{{cmid}}` - The module instance number (integer)
* `{{sesskey}}` - The session key, useful for ajax scripts

> To support more variables, modify `questionnaire_question_javascript()` in `lib.php`

# Original README

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
