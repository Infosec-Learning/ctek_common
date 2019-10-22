class DrupalBehavior {

  static isDebug(settings) {
    return settings.env !== 'prod';
  }

  attach(context, settings) {
    if (this._firstInvocation === undefined) {
      this._firstInvocation = true;
    }
    if (this._firstInvocation) {
      this.settings = settings;
      this.debug = DrupalBehavior.isDebug(settings);
      this.onReady();
    }
    this.onContent(context);
    this._firstInvocation = false;
  }

  detach(context) {
    this.onRemoveContent(context);
  }

  onReady() {}

  onContent(context) {}

  onRemoveContent(context) {}

  static register(ns, name, behavior) {
    const key = ns === null ? name : (ns + '_' + name);
    if (DrupalBehavior.isDebug(drupalSettings)) {
      console.log(`Registered Behavior: ${key}`);
    }
    Drupal.behaviors[key] = new behavior();
  }

  static resolve(ns, name) {
    return Drupal.behaviors[ns + '_' + name];
  }

}

/**
 * Ensure other themes and modules can benefit from this class.
 * @type {DrupalBehavior}
 */
window.DrupalBehavior = DrupalBehavior;
window.$ = jQuery;
