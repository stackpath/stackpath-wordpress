'use strict';

/**
 * Load a site's bandwidth, request, and StackPath POP aggregated metrics
 *
 * Display these metrics in the analytics graphs on the StackPath WordPress
 * plugin page.
 *
 * @param {string} duration
 */
function stackPathLoadMetrics(duration = '30 days') {
  /**
   * Draw a cache hit/miss doughnut chart
   *
   * @param {object} edgeMetrics
   * @param {object} originMetrics
   * @param {string} id
   * @param {string} key
   */
  const drawCacheHitMissChart = (edgeMetrics, originMetrics, id, key) => {
    const edgeIndex = edgeMetrics.metrics.indexOf(key);
    const originIndex = originMetrics.metrics.indexOf(key);

    const edgeSamples = edgeMetrics.samples.map(sample => sample.values[edgeIndex]);
    const originSamples = originMetrics.samples.map(sample => sample.values[originIndex]);

    const originTotal = originSamples.reduce((a, b) => a + b, 0);
    const edgeTotal = edgeSamples.reduce((a, b) => a + b, 0);
    const total = originTotal + edgeTotal;

    // Draw the chart
    new Chart(jQuery(id), {
      type: 'doughnut',
      data: {
        labels: ['Cache Hits', 'Cache Misses'],
        datasets: [
          {
            backgroundColor: ['rgba(10, 56, 171, 0.5)', 'rgba(237, 32, 39, 0.5)'],
            hoverBackgroundColor: ['rgba(10, 56, 171)', 'rgba(237, 32, 39)', ],
            data: total === 0 || total === 0.0 ? [1.0, 1.0] : [edgeTotal, originTotal]
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
          display: false,
        },
        tooltips: {
          callbacks: {
            // Show the percentage in the tooltip label
            label: (i, d) => {
              const label = d.labels[i.index];
              const percentage = total === 0 || total === 0.0 ? 0.0 : d.datasets[0].data[i.index] / total * 100;

              return `${label} (${percentage.toFixed(1)}%)`;
            }
          }
        },
      }
    });
  };

  /**
   * Draw a city-based metrics doughnut chart
   *
   * @param {array} popMetrics
   * @param {string} id
   * @param {string} units
   * @param {boolean} floatSamples
   * @param {string} key
   * @param {string} color
   */
  const drawPopChart = (popMetrics, id, units, floatSamples, key, color) => {
    // Consolidate known StackPath POP locations into cities and store POP
    // totals per city.
    let locations = [
      { city: 'Amsterdam, NL',   prefix: 'AM', total: 0.0 },
      { city: 'Ashburn, US',     prefix: 'DC', total: 0.0 },
      { city: 'Atlanta, US',     prefix: 'AT', total: 0.0 },
      { city: 'Chicago, US',     prefix: 'CH', total: 0.0 },
      { city: 'Dallas, US',      prefix: 'DA', total: 0.0 },
      { city: 'Denver, US',      prefix: 'DE', total: 0.0 },
      { city: 'Frankfurt, DE',   prefix: 'FR', total: 0.0 },
      { city: 'Hong Kong, HK',   prefix: 'WA', total: 0.0 },
      { city: 'Los Angeles, US', prefix: 'LA', total: 0.0 },
      { city: 'London, UK',      prefix: 'LO', total: 0.0 },
      { city: 'Madrid, ES',      prefix: 'MA', total: 0.0 },
      { city: 'Manila, PH',      prefix: 'MN', total: 0.0 },
      { city: 'Melbourne, AU',   prefix: 'ME', total: 0.0 },
      { city: 'Miami, US',       prefix: 'MI', total: 0.0 },
      { city: 'Milan, IT',       prefix: 'ML', total: 0.0 },
      { city: 'New York, US',    prefix: 'NY', total: 0.0 },
      { city: 'Paris, FR',       prefix: 'PA', total: 0.0 },
      { city: 'San Jose, US',    prefix: 'SJ', total: 0.0 },
      { city: 'São Paulo, BR',   prefix: 'SP', total: 0.0 },
      { city: 'Seattle, US',     prefix: 'SE', total: 0.0 },
      { city: 'Seoul, SK',       prefix: 'SL', total: 0.0 },
      { city: 'Singapore, SG',   prefix: 'SI', total: 0.0 },
      { city: 'Stockholm, SE',   prefix: 'SK', total: 0.0 },
      { city: 'Sydney, AU',      prefix: 'SY', total: 0.0 },
      { city: 'Tokyo, JP',       prefix: 'TK', total: 0.0 },
      { city: 'Toronto, CA',     prefix: 'TR', total: 0.0 },
      { city: 'Warsaw, PL',      prefix: 'WA', total: 0.0 },
    ];

    // Search through the raw metrics for the right location in the map, then
    // sum its total value.
    const j = popMetrics[0].metrics.indexOf(key);
    popMetrics.forEach(metrics => {
      const i = locations.findIndex(location => metrics.key.startsWith(location.prefix));

      metrics.samples.forEach(sample => {
        if (sample.values.length > 0) {
          locations[i].total += sample.values[j];
        }
      })
    });

    // Sort the locations by their totals and only show non-zero total locations.
    const displayLocations = locations.filter(location => location.total > 0).sort((a, b) => {
      if (a.total === b.total) {
        return 0;
      }

      return a.total > b.total ? -1 : 1;
    });

    // Determine the grand total for all locations for tooltip percentages.
    const total = displayLocations.reduce((a, b) => a + b.total, 0.0);

    // Draw the chart
    new Chart(jQuery(id), {
      type: 'doughnut',
      data: {
        labels: total === 0 || total === 0.0
          ? locations.map(location => location.city)
          : displayLocations.map(location => location.city),
        datasets: [
          {
            backgroundColor: `rgba(${color}, 0.5)`,
            hoverBackgroundColor: `rgba(${color})`,
            data: total === 0 || total === 0.0
              ? locations.map(() => 1.0)
              : displayLocations.map(location => floatSamples ? location.total.toFixed(2) : location.total)
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
          display: false,
        },
        tooltips: {
          callbacks: {
            // Add a percentage to the tooltip label
            label: (i, d) => {
              const city = d.labels[i.index];
              const value = total === 0 || total === 0.0 ? 0 : d.datasets[0].data[i.index];
              const percentage = total === 0 || total === 0.0 ? 0.0 : value / total * 100;

              return `${city} ${value} ${units} (${percentage.toFixed(1)}%)`;
            }
          }
        },
      }
    });
  };

  /**
   * Draw a metrics over time line chart
   *
   * @param {object} edgeMetrics
   * @param {object} originMetrics
   * @param {string} id
   * @param {string} type
   * @param {string} units
   * @param {boolean} floatSamples
   * @param {string} key
   */
  const drawLineChart = (edgeMetrics, originMetrics, id, type, units, floatSamples, key)  => {
    const edgeIndex = edgeMetrics.metrics.indexOf(key);
    const originIndex = originMetrics.metrics.indexOf(key);

    const edgeSamples = edgeMetrics.samples.map(sample => sample.values[edgeIndex]);
    const originSamples = originMetrics.samples.map(sample => sample.values[originIndex]);

    const edgeTotal = edgeSamples.reduce((a, b) => a + b, 0.0);
    const originTotal = originSamples.reduce((a, b) => a + b, 0.0);

    new Chart(jQuery(id), {
      type: 'line',
      data: {
        labels: edgeMetrics.samples.map(sample => sample.values[0]),
        datasets: [
          {
            label: `Edge ${type} (${floatSamples ? edgeTotal.toFixed(2): edgeTotal}` + (units === '' ? '' : ` ${units}`) + ' Total)',
            backgroundColor: 'rgba(10, 56, 171, 0.5)',
            borderColor: 'rgb(10, 56, 171)',
            data: edgeSamples.map(sample => floatSamples ? sample.toFixed(2) : sample)
          },
          {
            label: `${type} to Origin (${floatSamples ? originTotal.toFixed(2) : originTotal}` + (units === '' ? '' : ` ${units}`) + ' Total)',
            backgroundColor: 'rgba(237, 32, 39, 0.5)',
            borderColor: 'rgb(237, 32, 39)',
            data: originSamples.map(sample => floatSamples ? sample.toFixed(2) : sample)
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        hover: {
          mode: 'nearest',
          intersect: true
        },
        tooltips: {
          mode: 'index',
          intersect: false,
          callbacks: {
            // Add the local count and remove the total count from the data point's label
            label: (i, d) => `${i.value} ${units} ${d.datasets[i.datasetIndex].label.replace(/\(.* Total\)$/, '')}`
          }
        },
        scales: {
          xAxes: [{
            type: 'time'
          }],
          yAxes: [{
            display: true,
            scaleLabel: {
              display: true,
              labelString: type + (units === '' ? '' : `(${units})`)
            }
          }]
        }
      }
    });
  };

  let edgeMetrics;
  let originMetrics;
  let popMetrics;

  const loadingMetrics = jQuery('#stackpath-loading-metrics');
  const loadingBandwidthMetricsLoading = jQuery('#stackpath-loading-bandwidth-metrics-loading');
  const loadingBandwidthMetricsDone = jQuery('#stackpath-loading-bandwidth-metrics-done');
  const loadingBandwidthMetricsError = jQuery('#stackpath-loading-bandwidth-metrics-error');
  const loadingBandwidthMetricsErrorMessage = jQuery('#stackpath-loading-bandwidth-metrics-error-message');
  const loadingLocationMetricsLoading = jQuery('#stackpath-loading-location-metrics-loading');
  const loadingLocationMetricsDone = jQuery('#stackpath-loading-location-metrics-done');
  const loadingLocationMetricsError = jQuery('#stackpath-loading-location-metrics-error');
  const loadingLocationMetricsErrorMessage = jQuery('#stackpath-loading-location-metrics-error-message');
  const siteMetrics = jQuery('#stackpath-site-metrics');
  const loadingErrors = jQuery('#stackpath-loading-errors');

  // Start by hiding all error messages and showing the loading screen.
  loadingBandwidthMetricsErrorMessage.html('');
  loadingLocationMetricsErrorMessage.html('');
  loadingBandwidthMetricsDone.hide();
  loadingBandwidthMetricsError.hide();
  loadingLocationMetricsDone.hide();
  loadingLocationMetricsError.hide();
  loadingErrors.hide();
  loadingMetrics.show();
  siteMetrics.hide();

  // Make AJAX calls in parallel
  jQuery.when(
    // Load site metrics
    jQuery.post(
      ajaxurl,
      {
        action: 'stackpath_load_site_metrics',
        _stackpath_load_site_metrics_nonce: _stackpath_load_site_metrics_nonce,
        duration: duration
      }
    ).fail(response => {
      loadingBandwidthMetricsLoading.hide();
      loadingBandwidthMetricsError.show();
      loadingBandwidthMetricsErrorMessage.html(stackPathJQueryErrorResponseToString(response)).show();
      loadingErrors.show();
    }).done(data => {
      loadingBandwidthMetricsLoading.hide();
      const siteMetrics = JSON.parse(data);
      edgeMetrics = siteMetrics.filter(metricsSet => metricsSet.key === 'CDE')[0];
      originMetrics = siteMetrics.filter(metricsSet => metricsSet.key === 'CDO')[0];

      loadingBandwidthMetricsDone.show();
    }),

    // Load StackPath POP aggregated metrics
    jQuery.post(
      ajaxurl,
      {
        action: 'stackpath_load_pop_metrics',
        _stackpath_load_pop_metrics_nonce: _stackpath_load_pop_metrics_nonce,
        duration: duration
      }
    ).fail(response => {
      loadingLocationMetricsLoading.hide();
      loadingLocationMetricsError.show();
      loadingLocationMetricsErrorMessage.html(stackPathJQueryErrorResponseToString(response)).show();
      loadingErrors.show();
    }).done(data => {
      popMetrics = JSON.parse(data);
      loadingLocationMetricsLoading.hide();
      loadingLocationMetricsDone.show();
    })

    // When both calls are done then populate analytics graphs
  ).then(() => {
    if (typeof edgeMetrics === 'undefined' || typeof originMetrics === 'undefined' || typeof popMetrics === 'undefined') {
      return;
    }

    loadingMetrics.hide();
    siteMetrics.show();

    drawLineChart(edgeMetrics, originMetrics, '#stackpath-site-bandwidth-chart', 'Bandwidth', 'MB', true, 'xferUsedTotalMB');
    drawLineChart(edgeMetrics, originMetrics, '#stackpath-site-requests-chart', 'Requests', '', false, 'requestsCountTotal');
    drawPopChart(popMetrics, '#stackpath-pop-bandwidth-chart', 'MB', true, 'xferUsedTotalMB', '10, 56, 171');
    drawPopChart(popMetrics, '#stackpath-pop-requests-chart', 'Requests', false, 'requestsCountTotal', '237, 32, 39');
    drawCacheHitMissChart(edgeMetrics, originMetrics, '#stackpath-cache-hits-bandwidth-chart', 'xferUsedTotalMB');
    drawCacheHitMissChart(edgeMetrics, originMetrics, '#stackpath-cache-hits-requests-chart', 'requestsCountTotal');
  });
}

/**
 * Load and verify the site's Edge Address
 */
async function stackPathLoadEdgeAddress() {
  let edgeAddress;
  let valid;

  // Load Edge Address
  try {
    edgeAddress = (await stackPathFetchAjax('stackpath_load_edge_address')).edgeAddress;
    jQuery('#stackpath-loading-account-stacks-loading').hide();
  } catch (e) {
    const message = await stackPathErrorResponseToString(e);

    jQuery('#stackpath-loading-edge-address-error').show();
    jQuery('#stackpath-loading-edge-address-error-message').html(message).show();
    jQuery('#stackpath-validating-edge-address-error-message').html(': Skipped').show();
    jQuery('#stackpath-loading-errors').show();

    throw e;
  } finally {
    jQuery('#stackpath-loading-edge-address-loading').hide();
  }

  jQuery('.stackpath-edge-address-hostname').html(edgeAddress);
  jQuery('#stackpath-loading-edge-address-done').show();
  jQuery('#stackpath-validating-edge-address-loading').show();

  // Validate the Edge Address
  try {
    valid = (await stackPathFetchAjax('stackpath_verify_edge_address', {edgeAddress: edgeAddress})).valid;
    jQuery('#stackpath-validating-edge-address-done').show();
    jQuery('#stackpath-loading-edge-address').hide();
  } catch (e) {
    const message = await stackPathErrorResponseToString(e);

    jQuery('#stackpath-validating-edge-address-error').show();
    jQuery('#stackpath-validating-edge-address-error-message').html(message).show();
    jQuery('#stackpath-loading-errors').show();
  } finally {
    jQuery('#stackpath-validating-edge-address-loading').hide();
  }

  if (valid) {
    jQuery('#stackpath-site-edge-address-valid').show();
  } else {
    jQuery('#stackpath-site-edge-address-invalid').show();
  }

  jQuery('#stackpath-site-edge-address').show();
}

// Load the site's Edge Address
stackPathLoadEdgeAddress();

// Make the initial call to load metrics for the past 30 days
stackPathLoadMetrics();
