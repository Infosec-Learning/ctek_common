
class Profiler extends DrupalBehavior {

  onReady() {
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
    $('body').append($adminBar);
  }

}

DrupalBehavior.register('ctek_common', 'info', Profiler);
