/**
* DO NOT EDIT THIS FILE.
* All changes should be applied to ./modules/quickedit/js/models/EditorModel.es6.js
* See the following change record for more information,
* https://www.drupal.org/node/2873849
* @preserve
**/

(function (Backbone, Drupal) {

  'use strict';

  Drupal.quickedit.EditorModel = Backbone.Model.extend({
    defaults: {
      originalValue: null,

      currentValue: null,

      validationErrors: null
    }

  });
})(Backbone, Drupal);