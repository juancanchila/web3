/**
 * @file
 * File  layout-builder-ui.js.
 *
 * Event handler on layout builder editor.
 */

(function ($, Drupal, drupalSettings, Sortable) {

  Drupal.behaviors.node_layout_builder = {
    attach: function (context, settings) {
      const data = JSON.stringify(drupalSettings.node_layout_builder.data);

      if (typeof context['location'] !== 'undefined') {
        if (Object.keys(drupalSettings.node_layout_builder.data).length > 0) {
          if ($(".add-templates").length > 0) {
            $(".add-templates").css('display', 'none');
          }
        }
      }

      // Show/hide button add section.
      $(".nlb-wrapper section").on({
        mouseenter: function () {
          $(this).find(".btn-add-section:eq(0)").css("opacity", 1);
        }, mouseleave: function () {
          $(this).find(".btn-add-section:eq(0)").css("opacity", 0);
        }
      });

      // Show / hide element bar button action.
      $(".element").on({
        mouseenter: function () {
          $(this).find(".nbl-bar-buttons-action:eq(0)").css("display", "block");
        }, mouseleave: function () {
          $(this).find(".nbl-bar-buttons-action:eq(0)").css("display", "none");
        }
      });

      // Hightlight / background color of element selected.
      $(".nbl-bar-buttons-action").on({
        mouseenter: function () {
          const type = $(this).attr('data-type');
          let bgColor = 'transparent';

          switch (type) {
            case 'section':
              bgColor = '#ffffe0';
              break;

            case 'row':
              bgColor = '#e4f5ff';
              break;

            case 'column':
              bgColor = '#f7e1e1';
              break;

            default:
              bgColor = '#FDDADA';
              break;
          }
          $(this).parent().addClass('hover-element-' + type);
        }, mouseleave: function () {
          const type = $(this).attr('data-type');
          $(this).parent().removeClass('hover-element-' + type);
        }
      });

      nlb_ui_init(context, settings);
    }
  };

  /**
   *
   * @param context
   * @param settings
   */
  function nlb_ui_init(context, settings) {
    // Get Data and id entity.
    const nid = drupalSettings.node_layout_builder.nid;

    // Drag drop section.
    $('.nlb-wrapper').each(function (index) {
      const el = $(this)[0];
      Sortable.create(el, {
        group: 'sections',
        handle: 'section > .nbl-bar-buttons-action > ul li .icon-move',
        swapThreshold: 1,
        invertSwap: true,
        chosenClass: 'chosen-background-class',
        onEnd: function (evt) {
          const itemEl = evt.item;
          const index = evt.newIndex;
          const from = itemEl.id;
          const to = undefined;
          nlb_data_sortable(nid, from, to, index);
        },
      });
    });

    // Drag drop row.
    $('.section .container-fluid').each(function (index) {
      const el = $(this)[0];
      Sortable.create(el, {
        group: 'rows',
        handle: '.row > .nbl-bar-buttons-action > ul li .icon-move',
        swapThreshold: 1,
        invertSwap: true,
        chosenClass: 'chosen-background-class',
        onEnd: function (evt) {
          const itemEl = evt.item;
          const index = evt.newIndex;
          const from = itemEl.id;
          const to = evt.to.offsetParent.id;
          nlb_data_sortable(nid, from, to, index);
        },
      });
    });

    // Drag drop column.
    $('.element.row').each(function (index) {
      const el = $(this)[0];
      Sortable.create(el, {
        group: 'columns',
        handle: '.column > .nbl-bar-buttons-action > ul li .icon-move',
        swapThreshold: 1,
        invertSwap: true,
        chosenClass: 'chosen-background-class',
        onEnd: function (evt) {
          const itemEl = evt.item;
          let index = evt.newIndex;
          if (index > 0) {
            index = index - 1;
          }
          const from = itemEl.id;
          const to = evt.to.id;
          nlb_data_sortable(nid, from, to, index);
        },
      });
    });

    // Drag drop other element children.
    $('.column').each(function (index) {
      const el = $(this)[0];
      Sortable.create(el, {
        group: 'elements',
        handle: '.column .element .nbl-bar-buttons-action > ul li .icon-move',
        swapThreshold: 1,
        invertSwap: true,
        chosenClass: 'chosen-background-class',
        onEnd: function (evt) {
          const itemEl = evt.item;
          let index = evt.newIndex;
          if (index > 0) {
            index = index - 1;
          }
          const from = itemEl.id;
          const to = evt.to.id;
          nlb_data_sortable(nid, from, to, index);
        },
      });
    });

    // Save data.
    $('.nlb-wrapper', context).each(function () {
      // Save data layout.
      $('.nlb-save-data').on('click', function (e) {
        e.preventDefault();
        nlb_data_save(nid);
      });
      // Save data template.
      $('.nlb-save-template').on('click', function (e) {
        e.preventDefault();
        alert('pl');
      });
    });
  }

  /**
   * Update order element in data node entity.
   *
   * @param nid
   * @param from
   * @param to
   * @param index
   */
  function nlb_data_sortable(nid, from, to, index) {
    $.ajax({
      type: 'POST',
      url: '/node-layout-builder/sortable/' + nid + '/' + from + '/' + to + '/' + index
    }).done(function (res) {
      toastr.success('Change position');
    });
  }

  /**
   * Save data.
   *
   * @param nid
   */
  function nlb_data_save(nid) {
    $.ajax({
      type: 'GET',
      url: '/node-layout-builder/element/save/' + nid
    }).done(function (res) {
      if (res.msg) {
        toastr.success(res.msg);
      }
    });
  }

  /**
   * Save data template.
   *
   * @param nid
   */
  function nlb_data_template_save(nid) {
    $.ajax({
      type: 'GET',
      url: '/node-layout-builder/element/save-template/' + nid
    }).done(function (res) {
      if (res.msg) {
        toastr.success(res.msg);
      }
    });
  }

})(jQuery, Drupal, drupalSettings, Sortable);
