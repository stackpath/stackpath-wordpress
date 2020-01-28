'use strict';

/**
 * A helper for calling WordPress AJAX actions
 *
 * @param {string} action
 * @param {object} body
 * @returns {Promise<Response>}
 */
async function stackPathFetchAjax(action, body = {}) {
  // Build the POST body to send to WordPress.
  const bodyParameters = {
    'action': action,
  };

  bodyParameters[`_${action}_nonce`] = eval(`_${action}_nonce`);
  Object.keys(body).forEach(k => {
    bodyParameters[k] = body[k];
  });

  const response = await fetch(ajaxurl, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: Object.keys(bodyParameters)
      .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(bodyParameters[k]))
      .join('&'),
  });

  if (response.ok) {
    return await response.json();
  }

  throw response;
}

/**
 * Convert AJAX error response text into a user-facing error message
 *
 * Error responses are assumed to be JSON representations of
 * \StackPath\WordPress\Message objects.
 *
 * @param {Response} response
 * @returns {string}
 */
async function stackPathErrorResponseToString(response) {
  let message = '';
  const responseText = await response.text();

  try {
    const parsed = JSON.parse(responseText.trim());

    if (parsed.hasOwnProperty('title') && parsed.title.trim() !== '') {
      message = parsed.title.trim();
    }

    if (parsed.hasOwnProperty('description') && parsed.description !== null && parsed.description.trim() !== '') {
      message = message.replace(/\.$/, '');
      message += `. ${parsed.description.trim()}`;
    }
  } catch (e) {
    message = response.statusText;
  }

  return message.replace(/\.$/, '');
}

/**
 * Convert AJAX error response text into a user-facing error message
 *
 * Error responses are assumed to be JSON representations of
 * \StackPath\WordPress\Message objects.
 *
 * @param {Object} response
 * @returns {string}
 */
async function stackPathJQueryErrorResponseToString(response) {
  let message = response.responseText.trim();

  try {
    const parsed = JSON.parse(message);

    if (parsed.hasOwnProperty('title') && parsed.title.trim() !== '') {
      message = parsed.title.trim();
    }

    if (parsed.hasOwnProperty('description') && parsed.description !== null && parsed.description.trim() !== '') {
      message = message.replace(/\.$/, '');
      message += `. ${parsed.description.trim()}`;
    }
  } catch (e) {
    message = response.statusText;
  }
console.error(message);
  return message.replace(/\.$/, '');
}

/**
 * Quick and easy HTML entity escaping
 *
 * @param {string} input
 * @returns {string}
 */
function escapeHtml(input) {
  return input.replace(/[&<>"'\/]/g, function (s) {
    const entities = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': '&quot;',
      "'": '&#39;',
      "/": '&#x2F;',
    };

    return entities[s];
  });
}
