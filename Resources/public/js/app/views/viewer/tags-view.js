/** @lends TagsView */
define(function(require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * Tags view, able to handle tags array in model.
     *
     * Usage sample:
     * ```javascript
     * var tagsView = new TagsView({
     *     model: new Backbone.Model({
     *         tags: [
     *             {id: 1, name: 'tag1'},
     *             {id: 2, name: 'tag2'},
     *             // ...
     *         ]
     *     }),
     *     fieldName: 'tags', // should match tags field name in model
     *     autoRender: true
     * });
     * ```
     *
     * @class
     * @augments BaseView
     * @exports TagsView
     */
    var TagsView = BaseView.extend(/** @exports TagsView.prototype */{
        template: require('tpl!orotag/templates/viewer/tags-view.html'),
        listen: {
            'change model': 'render'
        },
        initialize: function(options) {
            this.fieldName = options.fieldName;
            return TagsView.__super__.initialize.apply(this, arguments);
        },
        getTemplateData: function() {
            return {
                model: this.model.toJSON(),
                fieldName: this.fieldName,
                tagSortCallback: this.tagSortCallback
            };
        },
        tagSortCallback: function(a, b) {
            return (a.owner ? 1 : 0) - (b.owner ? 1 : 0);
        }
    });

    return TagsView;
});
