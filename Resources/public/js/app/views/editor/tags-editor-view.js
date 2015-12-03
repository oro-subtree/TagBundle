/** @lends TagsEditorView */
define(function(require) {
    'use strict';

    /**
     * Tags-select content editor. Please note that it requires column data format
     * corresponding to tags-cell.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrid:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Full configuration
     *       {column-name-1}:
     *         inline_editing:
     *           editor:
     *             view: orodatagrid/js/app/views/editor/tags-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *               maximumSelectionLength: 3
     *           validation_rules:
     *             NotBlank: true
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.maximumSelectionLength | Optional. Maximum selection length
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.metadata - Editor metadata
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {string} options.maximumSelectionLength - Maximum selection length
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     *
     * @augments [AbstractRelationEditorView](./abstract-relation-editor-view.md)
     * @exports TagsEditorView
     */
    var TagsEditorView;
    var AbstractRelationEditorView = require('oroform/js/app/views/editor/abstract-relation-editor-view');
    var _ = require('underscore');
    var $ = require('jquery');

    TagsEditorView = AbstractRelationEditorView.extend(/** @exports TagsEditorView.prototype */{
        className: 'tags-select-editor',
        DEFAULT_PER_PAGE: 20,
        initialize: function(options) {
            TagsEditorView.__super__.initialize.apply(this, arguments);
        },

        getInitialResultItem: function() {
            return this.getModelValue().map(function(item) {
                return {
                    id: item.id,
                    label: item.name
                };
            });
        },

        getSelect2Options: function() {
            var _this = this;
            _this.currentData = null;
            _this.firstPageData = {
                results: [],
                more: false,
                isDummy: true
            };
            return {
                placeholder: this.placeholder || ' ',
                allowClear: true,
                openOnEnter: false,
                selectOnBlur: false,
                multiple: true,
                id: 'label',
                formatSelection: function(item) {
                    return item.label;
                },
                formatResult: function(item) {
                    return item.label;
                },
                initSelection: function(element, callback) {
                    callback(_this.getInitialResultItem());
                },
                query: function(options) {
                    _this.currentTerm = options.term;
                    _this.currentPage = options.page;
                    _this.currentCallback = options.callback;
                    if (options.page === 1) {
                        // immediately show first item
                        _this.showResults();
                    }
                    options.callback = function(data) {
                        _this.currentData = data;
                        if (data.page === 1) {
                            _this.firstPageData = data;
                        }
                        _this.showResults();
                    };
                    if (_this.currentRequest && _this.currentRequest.term !== '' &&
                        _this.currentRequest.state() !== 'resolved') {
                        _this.currentRequest.abort();
                    }
                    var autoCompleteUrlParameters = _.extend(_this.model.toJSON(), {
                        term: options.term,
                        page: options.page,
                        per_page: _this.perPage
                    });
                    if (options.term !== '' &&
                        !_this.autocompleteApiAccessor.isCacheExistsFor(autoCompleteUrlParameters)) {
                        _this.debouncedMakeRequest(options, autoCompleteUrlParameters);
                    } else {
                        _this.makeRequest(options, autoCompleteUrlParameters);
                    }
                }
            };
        },

        showResults: function() {
            var data;
            if (this.currentPage === 1) {
                data = $.extend({}, this.firstPageData);
                data.results = this.filterTermFromResults(this.currentTerm, data.results);
                if (this.currentPage === 1) {
                    if (this.isValidTerm(this.currentTerm)) {
                        data.results.unshift({
                            id: this.currentTerm,
                            label: this.currentTerm
                        });
                    } else {
                        if (this.firstPageData.isDummy) {
                            // do not update list until choices will be loaded
                            return;
                        }
                    }
                }
                data.results.sort(_.bind(this.tagSortCallback, this));
            } else {
                data = $.extend({}, this.currentData);
                data.results = this.filterTermFromResults(this.currentTerm, data.results);
            }
            this.currentCallback(data);
        },

        filterTermFromResults: function(term, results) {
            results = _.clone(results);
            for (var i = 0; i < results.length; i++) {
                var result = results[i];
                if (result.label === term) {
                    results.splice(i, 1);
                    break;
                }
            }
            return results;
        },

        tagSortCallback: function(a, b) {
            return this.getTermSimilarity(a.label) - this.getTermSimilarity(b.label);
        },

        getTermSimilarity: function(term) {
            var lowerCaseTerm = term.toLowerCase();
            var index = lowerCaseTerm.indexOf(this.currentTerm.toLowerCase());
            if (index === -1) {
                return 1000;
            }
            return index;
        },

        isValidTerm: function(term) {
            return _.isString(term) && term.length > 0;
        },

        getModelValue: function() {
            return this.model.get(this.fieldName) || [];
        },

        getFormattedValue: function() {
            return this.getModelValue().map(function(item) {
                return item.id;
            }).join(',');
        },

        isChanged: function() {
            return this.getValue() !== this.getModelValue().map(function(item) {
                return item.id;
            }).join(',');
        },

        getServerUpdateData: function() {
            var data = {};
            var select2Data = this.$('.select2-container').select2('data');
            data[this.fieldName] = select2Data.map(function(item) {
                return {
                    name: item.label
                };
            });
            return data;
        },

        getModelUpdateData: function() {
            var data = {};
            var select2Data = this.$('.select2-container').select2('data');
            data[this.fieldName] = select2Data.map(function(item) {
                return {
                    id: item.id,
                    name: item.label
                };
            });
            return data;
        }
    }, {
        processMetadata: AbstractRelationEditorView.processMetadata
    });

    return TagsEditorView;
});
