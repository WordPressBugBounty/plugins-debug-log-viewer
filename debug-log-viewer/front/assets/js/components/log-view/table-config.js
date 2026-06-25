/* global $, MutationObserver, dbg_lv_backend_data */
import { t, updateFilterCount } from '../../utils.js'
import { renderLogTypeBadge, renderDescription, convertToTimestamp, renderDatetime, renderCount } from './renderers.js'
import { createDropdownMenuItems, isPremium } from './ui-utils.js'

/**
 * Returns true when the infinite-scroll mode is active for this user.
 */
export function isInfiniteScrollMode () {
  return isPremium() && window.dbg_lv_backend_data?.scroll_mode === 'INFINITE'
}

/**
 * Creates and returns the DataTable configuration object
 */
export function createDataTableConfig(initCallbacks) {
  const infiniteScroll = isInfiniteScrollMode()

  return {
    serverSide: false,
    stateSave: true,
    ordering: true,
    retrieve: true,
    bSort: true,
    order: [[0, 'desc']], // Sort by timestamp desc by default
    processing: true,
    language: {
      processing: `<div class="dlv-spinner-wrap"><span class="dlv-spinner"></span><span class="dlv-spinner-label">${t('loading_in_process')}</span></div>`,
      search: '_INPUT_',
      lengthMenu: '_MENU_',
      emptyTable: t('empty_log_table'),
      zeroRecords: t('no_matching_records')
    },

    // Pagination vs infinite scroll
    paging: !infiniteScroll,
    pageLength: 10,
    ...(infiniteScroll ? { scrollY: '60vh', scrollCollapse: true } : {}),

    layout: {
      topStart: null, // remove pageLength from top default position
      topEnd: null, // remove search from top default position
      top8Start: [
        'pageLength',
        {
          buttons: [
            {
              extend: 'colvis',
              className: 'column-visibility-btn',
              text: t('show-hide')
            },
            {
              extend: 'csv',
              className: 'csv-export-btn d-none', // Hidden, we'll trigger it from the dropdown
              filename: 'debug-log-export',
              exportOptions: {
                format: {
                  body: (data) => {
                    // Clean up HTML tags from the data
                    return $('<div>').html(data).text()
                  }
                }
              }
            }
          ]
        },
        $(`
          <div class="time-filter">
            <div class="datetime-range-buttons btn-group">
              <button class="btn btn-sm btn-outline-primary" value="5m">5m</button>
              <button class="btn btn-sm btn-outline-primary" value="30m">30m</button>
              <button class="btn btn-sm btn-outline-primary" value="1h">1h</button>
              <button class="btn btn-sm btn-outline-primary" value="12h">12h</button>
              <button class="btn btn-sm btn-outline-primary active" value="all">All</button>
              ${isPremium()
            ? '<button class="btn btn-sm btn-outline-primary custom-range-btn" value="custom">Custom</button>'
            : `<div class="position-relative d-inline-block">
                    <button class="btn btn-sm btn-outline-primary custom-range-btn pro-disabled" value="custom" disabled>Custom</button>
                    <span class="pro-badge freemius-checkout-trigger" title="Upgrade to Pro">Pro</span>
                </div>`
          }
            </div>
          </div>`),
        $(`<div class="d-flex ms-4 advanced-filter">
            <button class="btn btn-sm btn-outline-primary search-builder-btn" style="height: 30px; border:none">
              <i class="fa fa-filter"></i>
              <span class="filter-text">${t('filter')}</span>
              <span class="filter-counter"></span>
            </button>
          </div>`),
      ],
      top8End: [
        {
          search: {}
        },
        $(`<div class="dropdown more-dropdown">
          <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
          <span>${t('more')}</span>
          </button>
          <ul class="dropdown-menu more-menu" aria-labelledby="dropdownMenuButton">
            ${createDropdownMenuItems()}
          </ul>
        </div>`),
        $(`<button class="btn btn-primary refresh-log" title="${t('refresh_log')}"><i class="fa fa-solid fa-arrows-rotate"></i></button>`)

      ],
      top7: 'searchBuilder'
    },

    columns: [
      {
        data: 'timestamp',
        title: t('timestamp') || 'Timestamp',
        visible: false,
        render: function (data, type, row) {
          // If timestamp is missing, calculate it from datetime
          return data || convertToTimestamp(row.datetime)
        }
      },
      {
        data: 'type',
        title: t('type') || 'Type',
        orderable: false,
        render: renderLogTypeBadge
      },
      {
        data: 'datetime',
        title: t('last_seen') || 'Last seen',
        className: 'datetime',
        width: '10%',
        render: renderDatetime
      },
      {
        data: 'count',
        title: t('count') || 'Count',
        className: 'count-col',
        width: '5%',
        render: renderCount
      },
      { data: 'description', title: t('description') || 'Description', className: 'description ph-no-capture', orderable: false, render: renderDescription, width: '50%' },
      { data: 'file', title: t('file') || 'File', className: 'file ph-no-capture', orderable: false, width: '25%' },
      { data: 'line', title: t('line') || 'Line', className: 'line', orderable: false, width: '15%' }
    ],
    autoWidth: false,
    stateSaveParams: function (settings, data) {
      // Don't persist Count column (index 3) visibility — it's controlled by the grouping setting
      if (data.columns && data.columns[3]) {
        delete data.columns[3].visible
      }
    },
    initComplete: function () {
      if (initCallbacks && typeof initCallbacks.onInitComplete === 'function') {
        initCallbacks.onInitComplete()
      }

      const table = this.api()
      $(document).on('click', '.export-csv-btn', function (e) {
        e.preventDefault()
        table.button('.csv-export-btn').trigger()
      })

      const updateFilterCountHandler = () => updateFilterCount(table)

      table.on('searchBuilder-rebuild', updateFilterCountHandler)
      table.on('searchBuilder-redraw', updateFilterCountHandler)
      table.on('draw', updateFilterCountHandler)

      const searchBuilderObserver = new MutationObserver(() => updateFilterCount(table))
      const searchBuilderContainer = document.querySelector('.dtsb-searchBuilder')
      if (searchBuilderContainer) {
        searchBuilderObserver.observe(searchBuilderContainer, {
          childList: true,
          subtree: true
        })
      }

      setTimeout(() => updateFilterCount(table), 500)
    }
  }
}
