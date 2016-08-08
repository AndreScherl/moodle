YUI.add('moodle-atto_clozeeditor-button', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    atto_clozeeditor
 * @copyright  2016 Matthias Ostermann  <mail@matthias-ostermann.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_clozeeditor-button
 */

/**
 * Atto text editor cloze plugin.
 *
 * @namespace M.atto_clozeeditor
 * @class Button
 * @extends M.editor_atto.EditorPlugin
 */

var COMPONENT = 'atto_clozeeditor',
        DIALOGUE = {
            WIDTH: '800px'
        },
TEMPLATE = '' +
        '<form class="{{CSS.FORM}}">' +
            // first element: input field to set question type and the points for the correct answer
            '<fieldset>' +
                '<div class="{{CSS.FORMSETTINGS}}">' +
                    '<label for="{{elementid}}_atto_clozeeditor_type">{{get_string "type" component}}</label>' +
                    '<select class="{{CSS.TYPE}}" id="atto_clozeeditor_type" />' +
                        '<option value="1" selected="selected">{{get_string "shortanswer" component}}</option>' +
                        '<option value="2">{{get_string "shortanswer_c" component}}</option>' +
                        '<option value="3">{{get_string "multichoice" component}}</option>' +
                        '<option value="4">{{get_string "multichoice_v" component}}</option>' +
                        '<option value="5">{{get_string "multichoice_h" component}}</option>' +
                        '<option value="6">{{get_string "numerical" component}}</option>' +
                    '</select>' +
                '</div>' +
                '<div class="{{CSS.FORMSETTINGS}}">' +
                    '<label for="{{elementid}}_atto_clozeeditor_points">{{get_string "points" component}}</label>' +
                    '<select class="{{CSS.POINTS}}" id="atto_clozeeditor_points" />' +
                        '<option value="1" selected="selected">1</option>' +
                        '<option value="2">2</option>' +
                        '<option value="3">3</option>' +
                        '<option value="4">4</option>' +
                    '</select>' +
                '</div>' +
            '</fieldset>' +
            '<br/>' +
            // second element: place to insert the input elements for the answers
            '<div>' +
                '<div class="{{CSS.ANSWER}}">' +
                    '<label for="answer_0">{{get_string "answer" component}}</label>' +
                '</div>' +
                '<div class="{{CSS.PERCENTAGE}}">' +
                    '<label for="{{elementid}}_atto_clozeeditor_percentage">{{get_string "percentage" component}}</label>' +
                '</div>' +
                '<div class="{{CSS.FEEDBACK}}">' +
                    '<label for="{{elementid}}_atto_clozeeditor_feedback">{{get_string "feedback" component}}</label>' +
                '</div>' +
            '</div>' +
            '<div class="{{CSS.OBJANSWER}}" id="atto_clozeeditor_answers" >' +
                '<fieldset id="atto_clozeeditor_fieldset_answer">' +
                    '<div class="{{CSS.ANSWER}}">' +
                        '<input class="{{CSS.ANSWER}}" id="answer_0" />' +
                    '</div>' +
                    '<div class="{{CSS.PERCENTAGE}}">' +
                        '<select class="{{CSS.PERCENTAGE}}" id="{{elementid}}_atto_clozeeditor_percentage" />' +
                            '<option value="100" selected="selected">100%</option>' +
                            '<option value="75">75%</option>' +
                            '<option value="67">66,6%</option>' +
                            '<option value="50">50%</option>' +
                            '<option value="34">33,3%</option>' +
                            '<option value="25">25%</option>' +
                            '<option value="0">0%</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="{{CSS.FEEDBACK}}">' +
                        '<input class="{{CSS.FEEDBACK}}" id="{{elementid}}_atto_clozeeditor_feedback" />' +
                    '</div>' +
                '</fieldset>' +
            '</div>' +
            '<br/>' +
            '<div id="addanswers"> </div>' +
            // third element: buttons to submit the answers
            '<div class="mdl-align">' +
                '<br/>' +
                '<button class="submit" type="submit">{{get_string "createcloze" component}}</button>' +
            '</div>' +
        '</form>',
        CSS = {
            TYPE: 'type',
            POINTS: 'points',
            OBJANSWER: 'objanswer',
            ANSWER: 'answer',
            PERCENTAGE: 'percentage',
            FEEDBACK: 'feedback',
            ADD: 'add',
            SUBMIT: 'submit',
            FORM: 'atto_form',
            WIDTH: 'customwidth',
            WIDTHUNIT: '%',
            FORMSETTINGS: 'formsettings'
        },
SELECTORS = {
    TYPE: '.' + CSS.TYPE,
    POINTS: '.' + CSS.POINTS,
    OBJANSWER: '.' + CSS.OBJANSWER,
    ANSWER: '.' + CSS.ANSWER,
    PERCENTAGE: '.' + CSS.PERCENTAGE,
    FEEDBACK: '.' + CSS.FEEDBACK,
    ADD: '.add',
    SUBMIT: '.submit',
    FORM: '.' + CSS.FORM,
    WIDTH: '.' + CSS.WIDTH
};


Y.namespace('M.atto_clozeeditor').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    /**
     * A reference to the current selection at the time that the dialogue
     * was opened.
     *
     * @property _currentSelection
     * @type Range
     * @private
     */
    _currentSelection: null,
    /**
     * The contextual menu that we can open.
     *
     * @property _contextMenu
     * @type M.editor_atto.Menu
     * @private
     */
    _contextMenu: null,
    /**
     * The last modified target.
     *
     * @property _lastTarget
     * @type Node
     * @private
     */
    _lastTarget: null,
    /**
     * The list of menu items.
     *
     * @property _menuOptions
     * @type Object
     * @private
     */
    _menuOptions: null,
    initializer: function () {
        this.addButton({
            icon: 'e/question',
            callback: this._displayDialogue
        });

        // We need custom highlight logic for this button.
        this.get('host').on('atto:selectionchanged', function () {
            if (this._existingCloze()) {
                this.highlightButtons();
            } else {
                this.unHighlightButtons();
            }
        }, this);

    },
    /**
     * Handle storing of an existing cloze string.
     *
     * @method _storeCloze
     * @param {EventFacade} e
     * @private
     */
    _storeCloze: function (e) {
        // storage for parts of the cloze string (innerHTML of clozeSpan)
        var points, type, locP, locH,
                tmpArray = new Array(3),
                txtArray = new Array(3);

        if (this._getClozeSpan()) {
            //Add the span to the EventFacade to save duplication in when showing the menu.
            e.clozeSpan = this.get('host').getSelectionParentNode().parentNode;

            // get content of existing cloze span
            var txt = e.clozeSpan.innerHTML;

            // remove {} from content; (indexOf: if the string starts or ends with a blank or something ...)
            var startIndex = txt.indexOf('{');
            var endIndex = txt.indexOf('}');
            txt = txt.slice(startIndex + 1, endIndex);

        } else if (this._getTinyCloze()) {
            // get content of existing cloze span
            var txt = this.get('host').getSelection().toString();

            // remove {} from content; (indexOf: if the string starts or ends with a blank or something ...)
            txt = txt.slice(1, txt.length - 1);
        }
        ;

        // split txt into points + questiontype + allanswers
        txtArray = txt.split(':');

        // translate question type to selection integer
        if (txtArray[1] === 'SHORTANSWER') {
            txtArray[1] = 1;
        } else if (txtArray[1] === 'SHORTANSWER_C') {
            txtArray[1] = 2;
        } else if (txtArray[1] === 'MULTICHOICE') {
            txtArray[1] = 3;
        } else if (txtArray[1] === 'MULTICHOICE_V') {
            txtArray[1] = 4;
        } else if (txtArray[1] === 'MULTICHOICE_H') {
            txtArray[1] = 5;
        } else if (txtArray[1] === 'NUMERICAL') {
            txtArray[1] = 6;
        } else {
            txtArray[1] = 1;
        }
        ;

        // set select forms for question type and maximum points
        Y.one('#atto_clozeeditor_type').set('value', txtArray[1]);
        Y.one('#atto_clozeeditor_points').set('value', txtArray[0]);

        // split allanswers to single answers
        txtArray[2] = txtArray[2].split('~');

        // split each answer to answer + percentage + feedback
        for (var i = 0; i < txtArray[2].length; i++) {
            // remove first %
            txtArray[2][i] = txtArray[2][i].slice(1, txtArray[2][i].length)

            // locate % and #
            locP = txtArray[2][i].indexOf('%');
            locH = txtArray[2][i].indexOf('#');

            // store answer, percentage and feedback
            // KNOWN PROBLEM: if there is a # in the answer, it's split at that point
            tmpArray[0] = txtArray[2][i].substring(locP + 1, locH);
            tmpArray[1] = txtArray[2][i].substring(0, locP);
            tmpArray[2] = txtArray[2][i].substring(locH + 1, txtArray[2][i].length);
            txtArray[2][i] = [];
            for (k = 0; k < tmpArray.length; k++) {
                txtArray[2][i][k] = tmpArray[k]
            }
            ;
        }
        ;

        for (m = 0; m < txtArray[2].length; m++) {
            for (var n = 0; n < 3; n++) {
                Y.one('#atto_clozeeditor_answers').get('children').item(m).get('children').item(n).get('children').item(0).set('value', txtArray[2][m][n]);
            }
            ;
            this._addAnswer();
        }
        ;

        return txtArray;
    },
    /**
     * Given the current selection, return the beginning of the cloze span-tag,
     * or false if not within a cloze span-tag.
     *
     * @method _getClozeSpan
     * @private
     */
    _getClozeSpan: function (e) {
        tmpNode = this.get('host').getSelectionParentNode().parentNode;

        if (tmpNode.nodeName.toLowerCase() === 'span' && tmpNode.getAttribute('name') === 'cloze') {
            return true;
        }
        ;

        return false;
    },
    /**
     * Given the current selection, return its content as a string,
     * if it begins with an { and end with a } (tinyMCE cloze).
     *
     * @method _getTinyCloze
     * @private
     */
    _getTinyCloze: function (e) {
        var txt = this.get('host').getSelection().toString();

        if (txt.length > 1) {
            if (txt[0] === '{' && txt[txt.length - 1] === '}') {
                return true;
            }
            ;
        }
        ;

        return false;
    },
    /**
     * Check whether we are in an existing cloze span or a tinyMCE cloze string
     *
     * @method _existingCloze
     * @private
     */
    _existingCloze: function (e) {
        // Find the cloze content in the surrounding text
        var selectedNode = this.get('host').getSelectionParentNode(),
                selection = this.get('host').getSelection();

        // Prevent looking for cloze content when we don't have focus.
        if (!this.get('host').isActive()) {
            return false;
        }
        ;

        // Note this is a document fragment and YUI doesn't like them.
        if (!selectedNode) {
            return false;
        }
        ;

        // We don't yet have a cursor selection somehow so we can't possible be looking for cloze content
        if (!selection || selection.length === 0) {
            return false;
        }
        ;

        if (this._getClozeSpan() || this._getTinyCloze()) {
            return true;
        }
        ;

        return false;
    },
    /**
     * Display the cloze tool dialogue
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function (e) {

        // Store the current selection.
        this._currentSelection = this.get('host').getSelection();
        if (this._currentSelection === false) {
            return;
        }
        ;

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('createcloze', COMPONENT),
            focusAfterHide: true,
            width: DIALOGUE.WIDTH
        }, true);

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent(e))
                .show();

        // Check if cloze span already exists --> store contents and update input fields
        // Check if selection begins with { and ends with } --> store contents and update input fields
        if (this._getClozeSpan() || this._getTinyCloze()) {
            var tmpArray = this._storeCloze(e);
        }
        ;
    },
    /**
     * Return the dialogue content for the tool, attaching any required events.
     *
     * @method _getDialogueContent
     * @private
     * @return {Node} The content to place in the dialogue.
     */
    _getDialogueContent: function (e) {

        var template = Y.Handlebars.compile(TEMPLATE);

        this._content = Y.Node.create(template({
            CSS: CSS,
            elementid: this.get('host').get('elementid'),
            component: COMPONENT
        }));

        this._content.one('#answer_0').on('valuechange', this._addAnswer, this);
        this._content.one('.submit').on('click', this._submitCloze, this);

        return this._content;
    },
    /**
     * Handle creation of a new cloze string.
     *
     * @method _setCloze
     * @param {EventFacade} e
     * @private
     */
    _submitCloze: function (e) {
        // create storage for answers
        var written = false;
        var type, points, clozestring;

        e.preventDefault();

        // Hide the dialogue.
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        // Set an id for the cloze span tag
        var clozeId = Y.guid();

        // Set the selection.
        this.get('host').setSelection(this._currentSelection);

        // Connect points variable to point select form
        type = e.currentTarget.ancestor(SELECTORS.FORM).one(SELECTORS.TYPE);
        points = e.currentTarget.ancestor(SELECTORS.FORM).one(SELECTORS.POINTS);

        // Build the Cloze string.
        // Example: {1:MULTICHOICE:%100%answer1#feedback1~%0%answer2#feedback2}
        clozestring = '{' + points.get('value');

        switch (parseInt(type.get('value'))) {
            case 1:
                clozestring += ':SHORTANSWER:';
                break;
            case 2:
                clozestring += ':SHORTANSWER_C:';
                break;
            case 3:
                clozestring += ':MULTICHOICE:';
                break;
            case 4:
                clozestring += ':MULTICHOICE_V:';
                break;
            case 5:
                clozestring += ':MULTICHOICE_H:';
                break;
            case 6:
                clozestring += ':NUMERICAL:';
                break;
            default:
                clozestring += ':SHORTANSWER:'
        }
        ;

        for (var i = 0; i < this._content.one('#atto_clozeeditor_answers').get('children').size(); i++) {

            tmpNode = this._content.one('#atto_clozeeditor_answers').get('children').item(i);

            if (tmpNode.get('children').item(0).get('children').item(0).get('value')) {

                // another answer has been written into the cloze string
                if (written) {
                    clozestring += '~';
                    written = false;
                }
                ;

                // write an answer into the cloze string
                clozestring += '%' + tmpNode.get('children').item(1).get('children').item(0).get('value') +
                        '%' + tmpNode.get('children').item(0).get('children').item(0).get('value') +
                        '#' + tmpNode.get('children').item(2).get('children').item(0).get('value');

                // set written to true
                written = true;
            }
            ;
        }
        ;

        clozestring += '}';

        if (this._getClozeSpan()) {
            // insert into the cloze span
            this.get('host').getSelectionParentNode().parentNode.innerHTML = clozestring;
        } else {
            clozestring = '<span name="cloze">' + clozestring + '</span>';
            // insert into the editor
            this.get('host').insertContentAtFocusPoint(clozestring);
        }
        ;

        // Mark the content as updated.
        this.markUpdated();

    },
    /**
     * Add a row for an additional answer.
     *
     * @method _addAnswer
     * @private
     */
    _addAnswer: function () {
        // detach event subscription of type 'valuechange' from node and its descendants
        this._content.one('#atto_clozeeditor_answers').purge(true, 'valuechange');

        // clone the set of input fields for the first answer
        tmpNode = Y.one('#atto_clozeeditor_fieldset_answer').cloneNode(true);

        // remove the texts from the clone's input fields
        tmpNode.get('children').item(0).get('children').item(0).set('value', '');
        tmpNode.get('children').item(2).get('children').item(0).set('value', '');

        // add an event subscription to the clone#s answer input field
        tmpNode.get('children').item(0).get('children').item(0).on('valuechange', this._addAnswer, this);

        // insert the clone into the dialogue
        Y.one('#atto_clozeeditor_answers').appendChild(tmpNode);

        return;
    }

}, {});

}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin", "moodle-editor_atto-menu", "event", "event-valuechange"]});
