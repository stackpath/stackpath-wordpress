'use strict';

/**
 * Load all stacks on an account
 *
 * @returns {Promise<Response>}
 */
async function stackPathLoadStacks() {
  try {
    const stacks = await stackPathFetchAjax('stackpath_find_stacks');
    jQuery('#stackpath-loading-account-stacks-loading').hide();

    return stacks;
  } catch (e) {
    const message = await stackPathErrorResponseToString(e);

    jQuery('#stackpath-loading-account-stacks-error').show();
    jQuery('#stackpath-loading-account-stacks-error-message').html(message);

    throw e;
  }
}

/**
 * Load all active sites in a stack
 *
 * @param {object} stack
 * @returns {Promise<Response>}
 */
async function stackPathLoadSites(stack) {
  jQuery("#stackpath-loading-account-sites").append(jQuery(
    `<div>Loading sites from <strong>${stack.name}</strong>
      <img id="stackpath-loading-account-sites-${stack.id}-loading" src="${_stackpath_admin_url}images/loading.gif" alt="loading">
      <span id="stackpath-loading-account-sites-${stack.id}-error" style="display: none">
        <img src="${_stackpath_admin_url}images/no.png" alt="error">
      </span>

      <span id="stackpath-loading-account-sites-${stack.id}-error-message"></span>
    </div>`
  ));

  try {
    const sites = await stackPathFetchAjax('stackpath_find_sites', {stack_id: stack.id});
    jQuery(`#stackpath-loading-account-sites-${stack.id}-loading`).hide();

    return sites;
  } catch (e) {
    const message = await stackPathErrorResponseToString(e);

    jQuery(`#stackpath-loading-account-sites-${stack.id}-loading`).hide();
    jQuery(`#stackpath-loading-account-sites-${stack.id}-error`).show();
    jQuery(`#stackpath-loading-account-sites-${stack.id}-error-message`).html(message);

    throw e;
  }
}

/**
 * Determine if a site has the given delivery domain
 *
 * @param {object} site
 * @param {string} deliveryDomain
 * @returns {Promise<boolean>}
 */
async function stackPathHasDeliveryDomain(site, deliveryDomain) {
  jQuery("#stackpath-loading-account-sites").append(jQuery(
    `<p style="margin-left: 20px; margin-top: 0; margin-bottom: 0">Searching ${site.label}
      <img id="stackpath-loading-account-site-${site.id}-loading" src="${_stackpath_admin_url}images/loading.gif" alt="loading">
      <span id="stackpath-loading-account-site-${site.id}-done" style="display: none">
        <img src="${_stackpath_admin_url}images/yes.png" alt="done">
      </span>
      <span id="stackpath-loading-account-site-${site.id}-error" style="display: none">
        <img src="${_stackpath_admin_url}images/no.png" alt="error">
      </span>

      <span id="stackpath-loading-account-site-${site.id}-done-message"></span>
      <span id="stackpath-loading-account-site-${site.id}-error-message"></span>
    </p>`
  ));

  try {
    const deliveryDomains = await stackPathFetchAjax(
      'stackpath_find_site_delivery_domains',
      {site_id: site.id, stack_id: site.stackId}
    );
    jQuery(`#stackpath-loading-account-site-${site.id}-loading`).hide();

    for (let i = 0; i < deliveryDomains.length; i++) {
      if (deliveryDomains[i] === deliveryDomain) {
        jQuery(`#stackpath-loading-account-site-${site.id}-done`).show();
        jQuery(`#stackpath-loading-account-site-${site.id}-done-message`).html('Found!');
        return true;
      }
    }

    return false;
  } catch (e) {
    const message = await stackPathErrorResponseToString(e);

    jQuery(`#stackpath-loading-account-site-${site.id}-error`).show();
    jQuery(`#stackpath-loading-account-site-${site.id}-error-message`).html(message);

    throw e;
  }
}

// Load account stacks, each stacks' sites, then check the sites' delivery
// domains for the WordPress instance's domain name.
(async () => {
  try {
    let found = false;
    let foundSite = {};
    const stacks = await stackPathLoadStacks();

    for (let i = 0; i < stacks.length; i++) {
      let options = '';
      stacks[i].sites = await stackPathLoadSites(stacks[i]);

      for (let j = 0; j < stacks[i].sites.length; j++) {
        options += `<option id="stackpath-site-${stacks[i].sites[j].id}" value="${stacks[i].id}_${stacks[i].sites[j].id}">
          ${escapeHtml(stacks[i].sites[j].label)}
        </option>`;

        found = found || await stackPathHasDeliveryDomain(stacks[i].sites[j], _stackpath_site_host);
        if (found && Object.keys(foundSite).length === 0) {
          foundSite = stacks[i].sites[j];
        }
      }

      jQuery('#stackpath-stack-id').append(jQuery(`<option value="${stacks[i].id}">${stacks[i].name}</option>`));
      jQuery('#stackpath-existing-site-id').append(jQuery(
        `<optgroup label="${escapeHtml(stacks[i].name)}">${options}</optgroup>`
      ));
    }

    if (found) {
      jQuery('#stackpath-existing-site-id').val(`${foundSite.stackId}_${foundSite.id}`);
      jQuery('#stackpath-site-found').show();
    } else {
      jQuery('#stackpath-site-not-found').show();
    }

    jQuery('#stackpath-attach-to-existing-site').show();
    jQuery('#stackpath-create-new-site').show();
  } catch (e) {
    jQuery('#stackpath-site-search-error-message').show();
  }
})();
