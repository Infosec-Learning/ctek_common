/**
 * Provides some useful information at the bottom right of the screen on non-production environments.
 *
 * @TODO provide opt-in/out configuration
 */
class Profiler extends DrupalBehavior {

  onReady(document) {
    super.onReady();
    const profilerData = JSON.parse($('[data-drupal-selector="ctek-common-profiler-json"]').text());
    const $adminBar = $('<div id="ctek-common-profiler" class="open" />');
    const maxlength = Math.max.apply(
      Math,
      Object
        .values(profilerData)
        .map(val => val.toString().length)
    );
    for (const [key, value] of Object.entries(profilerData)) {
      $adminBar.append('<div><span>'+ key + ':</span>' + value.toString().padEnd(maxlength) + '</div>');
    }
    $('body', document).append($adminBar);
  }

}

DrupalBehavior.register('ctek_common', 'profiler', Profiler);
