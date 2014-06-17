Data Layer
==============
**Get page meta data from Drupal to the client-side.**

This Drupal module outputs various page meta data, which can be useful for all kind of front-end uses.
The phase "data layer" is mostly a Google term, but it's more-or-less a standard for your application to communicate with Analytics and Tag Manager. It's genertic enough that other services managed in GTM can use application data, also you can use this data on your site to implement great client-side features, anonymous user tracking, etc.

**Issues:** Post problems or feature requests to the [Drupal project issue queue](https://drupal.org/project/issues/datalayer).

## Meta data output
In order to do fun and fancy things on the client-side it's critial nice to have easy and reliable access to the meta data about the pages of your site. This modules helps output that info. Yes, you could get some of this from the DOM, but that's messy.

You can configure what gets pushed out via the admin page. This includes global control over all entity properties. You can also control if taxonomy should be inluded, and which vocabularies should be exposed. Here's _some_ of what's available by default...
```json
{
  "nid" : "123",
  "title" : "My Cool Page",
  "entityType" : "node",
  "bundle" : "article",
  "uid" : "555",
  "language" : "en",
  "taxonomy" : {
    "special_category" : {
      "25" : "Term Name",
      "26" : "Another Term"
    },
    "my_types" : {
      "13" : "Some Tag",
      "14" : "Another Tag"
    }
  }
}
```

## Adding to the data layer

### Suggest entity properties
You can easily suggest additional entity properties to the Data Layer module by using the `hook_datalayer_output_meta()` function. Example:
```php
function my_module_datalayer_meta() {  
  return array(
    'my_entity_property',
  );
}
```

### Add data layer values
In order to easily add data layer properties and values on the fly within your code, use the `datalayer_add_dl()` function much like you would `drupal_add_js` or `drupal_add_css`.
NOTE: In that case of matching keys, any added property/value pairs can overwrite those already available via normal entity output. You _should_ be using the `datalayer_dl_alter()` function if that's the intent, as added properties are available there.
Example:
```php
function _my_module_myevent_func($argument = FALSE) {
  if ($argument) {
    datalayer_add_dl(array(
      'my_property' => $argument∏,
      'my_other_property' => _my_module_other_funct($argument),
    ));
  }
}
```

## Alter output

### Alter available properties
You can also alter what entity properties are available within the admin UI, and as candidates via the `hook_datalayer_meta_alter()` function. _You may want to take advantage of the entity agnostic menu object loader function found within the module._ For example you might want to hide author information in some special cases...
```php
function my_module_datalayer_meta_alter(&$properties) {
  // Override module norm in all cases.
  unset($properties['uid']);

  // Specific situation alteration...
  $type = false;
  if ($obj = _datalayer_menu_get_any_object($type)) {
    if ($type === 'node' && in_array(array('my_bundle', 'my_nodetype'), $obj->type)) {
      // Remove author names on some content type.
      if ($key = array_search('name', $properties)) {
        unset($properties[$key]);
      }
    }
    elseif ($type === 'my_entity_type') {
      // Remove some property on some entity type.
      if ($key = array_search('my_property', $properties)) {
        unset($properties[$key]);
      }
    }
  }
}
```

### Alter output values
You can also directly alter output bound data with the `hook_datalayer_dl_alter()` function. Use this to alter values found in normal entity output or added by `datalayer_add_dl()` within the same or other modules, to support good architecture.
```php
function my_module_datalayer_dl_alter(&$data_layer) {
  // Make the title lowercase on some node type.
  if (isset($data_layer['bundle']) && $data_layer['bundle'] == 'mytype') {
    $data_layer['title'] = strtolower($data_layer['title']);
  }
}
```

## Use the data layer client-side
There are lots of great client-side uses for your pages' data. You might act on this info like this...
```javascript
(function ($) {
  $(document).ready(function(){

    if (typeof dataLayer.taxonomy.my_category !== 'undefined') {
      if (dataLayer.taxonomy.my_category.hasOwnProperty('25')) {
        doMyAction(dataLayer.uid, dataLayer.language, dataLayer.title);
      }
    }

  });
})(jQuery);
```

## Dynamic additions
You add new data to the data layer dynamically.
```javascript
// Inform of link clicks.
$(".my-links").click(function() {
  dataLayer.push({ 'link-click': $(this).text() });
});

// Inform of Views filter changes.
$(".views-widget select.form-select").change(function() {
  dataLayer.push({
    'filter-set': $(this).find("option:selected").text()
  });
});
``` 

## Google
Chances are you're interested in this module to get data from Drupal into the data layer to pass on to Google Tag Manager.
To do this just check the box on the admin screen. If you want to more about working with Google services, refer to the [Tag Manager - Dev Guide](https://developers.google.com/tag-manager/devguide).

### Data Layer Helper
To employ more complex interactions with the data you may want load the [data-layer-helper](https://github.com/google/data-layer-helper) library. It provides the ability to "process messages passed onto a dataLayer queue," meaning listen to data provided to the data layer dynamicly.
To use, add the compiled source to the standard Drupal location of `sites/all/libraries/data-layer-helper/data-layer-helper.js` and check the box on the admin page to include it.
