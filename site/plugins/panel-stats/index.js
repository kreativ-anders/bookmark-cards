/**
 * Panel views "Bookmarks" and "Users › Statistics" with their sections,
 * see site/blueprints/dashboard/*.yml.
 *
 * Written as render functions: the Panel runs the Vue runtime without the
 * template compiler (panel.vue.compiler: false in config.php), so `template`
 * strings would never render.
 */
panel.plugin("kreativ-anders/panel-stats", {
  components: {
    /**
     * Dashboard view: header with optional buttons, sections laid out like a
     * site tab. Sections load from the API at `<parent>/sections/<name>`.
     */
    "k-dashboard-view": {
      props: {
        title: String,
        parent: String,
        tab: Object,
        buttons: { type: Array, default: () => [] }
      },
      render(h) {
        const buttons = this.buttons.map((button) => ({ size: "sm", variant: "filled", ...button }));

        return h("k-panel-inside", [
          // k-header checks $slots.buttons, so the buttons go in as a named (non-scoped) slot
          h("k-header", [
            this.title,
            buttons.length ? h("k-button-group", { slot: "buttons", props: { buttons } }) : null
          ]),
          h("k-sections", {
            props: {
              blueprint: this.parent,
              content: {},
              empty: "No statistics configured",
              parent: this.parent,
              tab: this.tab
            }
          })
        ]);
      }
    }
  },
  sections: {
    /**
     * `actionstats`: stats reports like `type: stats`, plus header buttons that
     * copy text to the clipboard (`copy`) or open a Panel dialog (`dialog`).
     */
    actionstats: {
      data() {
        return {
          headline: null,
          size: "large",
          reports: [],
          actions: [],
          empty: null
        };
      },
      async created() {
        const response = await this.load();
        this.headline = response.headline;
        this.size = response.size;
        this.reports = response.reports;
        this.actions = response.buttons || [];
        this.empty = response.empty;
      },
      computed: {
        buttons() {
          return this.actions
            .filter((action) => action.dialog || action.copy)
            .map((action) => ({
              icon: action.icon,
              text: action.text,
              click: () => this.run(action)
            }));
        }
      },
      methods: {
        run(action) {
          if (action.dialog) {
            this.$panel.dialog.open(action.dialog);
            return;
          }

          this.$helper.clipboard.write(action.copy);
          const count = this.reports.length;
          this.$panel.notification.success(`Copied ${count} ${count === 1 ? "entry" : "entries"}`);
        }
      },
      render(h) {
        let body = null;
        if (this.reports.length) {
          body = h("k-stats", { props: { reports: this.reports, size: this.size } });
        } else if (this.empty) {
          body = h("k-empty", { props: { icon: "image" } }, this.empty);
        }

        return h("k-section", { props: { headline: this.headline, buttons: this.buttons } }, [body]);
      }
    },

    /**
     * `chart`: horizontal bar list (layout: bars) or one stacked bar
     * with legend (layout: stack). Data comes from a site method.
     */
    chart: {
      data() {
        return {
          headline: null,
          layout: "bars",
          items: [],
          empty: null
        };
      },
      async created() {
        const response = await this.load();
        this.headline = response.headline;
        this.layout = response.layout;
        this.items = response.data;
        this.empty = response.empty;
      },
      computed: {
        total() {
          return this.items.reduce((sum, item) => sum + item.value, 0);
        },
        max() {
          return Math.max(1, ...this.items.map((item) => item.value));
        },
        segments() {
          return this.items.filter((item) => item.value > 0);
        },
        summary() {
          return this.items.map((item) => `${item.label}: ${item.value}`).join(", ");
        }
      },
      methods: {
        percent(value) {
          return this.total > 0 ? Math.round((value / this.total) * 100) + "%" : "0%";
        },
        color(item) {
          return { "--chart-color": `var(--chart-${item.color || "series-1"})` };
        },
        tooltip(item) {
          return `${item.label}: ${item.value}` + (item.info ? ` (${item.info})` : "");
        },
        renderStack(h) {
          return [
            h(
              "div",
              { class: "k-chart-stack", attrs: { role: "img", "aria-label": this.summary } },
              this.segments.map((item) =>
                h("span", {
                  key: item.label,
                  class: "k-chart-segment",
                  style: { ...this.color(item), flexGrow: item.value },
                  attrs: { title: this.tooltip(item) + " · " + this.percent(item.value) }
                })
              )
            ),
            h(
              "ul",
              { class: "k-chart-legend" },
              this.items.map((item) =>
                h("li", { key: item.label, attrs: { title: this.tooltip(item) } }, [
                  h("span", { class: "k-chart-swatch", style: this.color(item) }),
                  h("span", { class: "k-chart-label" }, item.label),
                  h("span", { class: "k-chart-value" }, [
                    item.value + " ",
                    h("small", this.percent(item.value))
                  ])
                ])
              )
            )
          ];
        },
        renderBars(h) {
          return h(
            "ul",
            { class: "k-chart-bars" },
            this.items.map((item) =>
              h("li", { key: item.label, attrs: { title: this.tooltip(item) } }, [
                h("span", { class: "k-chart-label" }, [
                  item.image
                    ? h("img", { class: "k-chart-logo", attrs: { src: item.image, alt: "", loading: "lazy" } })
                    : null,
                  h("span", item.label)
                ]),
                h("span", { class: "k-chart-track" }, [
                  h("span", {
                    class: "k-chart-bar",
                    style: { ...this.color(item), width: (item.value / this.max) * 100 + "%" }
                  })
                ]),
                h("span", { class: "k-chart-value" }, [
                  item.value + " ",
                  item.info ? h("small", item.info) : null
                ])
              ])
            )
          );
        }
      },
      render(h) {
        let body;
        if (this.total === 0) {
          body = h("p", { class: "k-chart-empty" }, this.empty);
        } else if (this.layout === "stack") {
          body = this.renderStack(h);
        } else {
          body = this.renderBars(h);
        }

        return h("k-section", { class: "k-chart-section", props: { headline: this.headline } }, [
          h("div", { class: "k-chart", attrs: { "data-layout": this.layout } }, [].concat(body))
        ]);
      }
    }
  }
});
