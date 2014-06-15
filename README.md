Data Layer
==============
**Get page meta data from Drupal to the client-side.**

This Drupal module outputs various page meta data, which can be useful for all kind of front-end uses.
The phase "data layer" is mostly a Google term, but it's more-or-less a standard for your application to communicate with Analytics and Tag Manager. It's genertic enough that other services managed in GTM can use application data, also you can use this data on your site to implement great client-side features, anonymous user tracking, etc.

**Issues:** Post problems or feature requests to the [Drupal project issue queue](https://drupal.org/project/issues/datalayer).

## Meta Data Output
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

## Suggest output properties
You can easily suggest additional entity properties to the Data Layer module by using the `hook_datalayer_output_meta()` function. Example:
```php
<?php
function my_module_datalayer_meta() {  
  return array(
    'my_special_entity_property',
  );
}
```

## Deeper output control
You can also alter what's available in more granular ways via the `hook_datalayer_meta_alter()` function. _You may want to take advantage of the entity agnostic menu object loader within the module._ For example you might want to hide author information in some special cases...
```php
<?php
function my_module_datalayer_meta_alter(&$properties) {
  $type = false;
  if ($obj = _datalayer_menu_get_any_object($type)) {
    if ($type === 'node' && $obj->type === 'my_bundle') {
      // Remove author names on some content type.
      if ($key = array_search('name', $properties)) {
        unset($properties[$key]);
      }
    }
    elseif ($type === 'my_entity_type') {
      // Remove some property on some entity.
      if ($key = array_search('my_property', $properties)) {
        unset($properties[$key]);
      }
    }
  }
}
```

## Use this data yourself
There are lots of great client-side uses for your pages' data. You might act on this info like this...
```javascript
(function ($) {
  Drupal.behaviors.my_module = {
    attach: function (context, settings) {

      var author = settings.dataLayer.uid,
          title = settings.dataLayer.title,
          lang = settings.dataLayer.language;

      if (typeof settings.dataLayer.taxonomy.my_category !== 'undefined') {
        if (settings.dataLayer.taxonomy.my_category.hasOwnProperty('25')) {
          doMySpecialAction(author, lang, title);
        }
      }

    }
  };
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
