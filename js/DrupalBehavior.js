/**
 * Provides a pretty ES2015 wrapper for the Drupal behaviors system, and augments its functionality by providing
 * structure that makes functions running in the context of page load easy to differentiate from functions running in
 * the context of a page fragment.
 */
class DrupalBehavior {

  constructor(key) {
    this.key = key;
  }

  static isDebug(settings) {
    return settings.env !== 'prod';
  }

  /**
   * Called by underlying Drupal.behaviors system. As such, this runs for page load and page fragment contexts.
   *
   * @param context
   * @param settings
   */
  attach(context, settings) {
    if (this._firstInvocation === undefined) {
      this._firstInvocation = true;
    }
    if (this._firstInvocation) {
      // Run for page load
      this.settings = settings;
      this.debug = DrupalBehavior.isDebug(settings);
      this.onReady(context);
      this._firstInvocation = false;
    }
    // Run for page load or page fragment
    this.onContent(context);
  }

  detach(context) {
    this.onRemoveContent(context);
  }

  /**
   * Extend to execute on page load.
   */
  onReady(document) {}

  /**
   * Extend to execute on page load or any page fragment.
   *
   * @param context - The affected DOM
   */
  onContent(context) {}

  /**
   * Extend to execute when a page fragment is removed.
   *
   * @param context - The affected DOM
   */
  onRemoveContent(context) {}

  /**
   * Registers a Drupal behavior with the underlying Drupal.behaviors object.
   *
   * @TODO add mechanism to allow for replacement of already-registered behaviors, but warn if not explicitly requested.
   *
   * @param {string} ns - The namespace to use to help prevent collisions. Can be null.
   * @param {string} name - The name of the behavior
   * @param {function} behavior - The DrupalBehavior instance
   * @param {...*} args - Additional constructor arguments
   */
  static register(ns, name, behavior, ...args) {
    const key = ns === null ? name : (ns + '.' + name);
    if (DrupalBehavior.isDebug(drupalSettings)) {
      console.log(`Registered Behavior: ${key}`);
    }
    Drupal.behaviors[key] = new behavior(key, ...args);
  }

}

/**
 * Ensure other themes and modules can benefit from this class.
 * @type {DrupalBehavior}
 */
window.DrupalBehavior = DrupalBehavior;
window.$ = jQuery;
