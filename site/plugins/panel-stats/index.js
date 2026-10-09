/**
 * Panel section `chart`: horizontal bar list (layout: bars) or one stacked bar
 * with legend (layout: stack). Data comes from a site method, see site.yml.
 */
panel.plugin("kreativ-anders/panel-stats", {
  sections: {
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
        }
      },
      template: `
        <k-section :headline="headline" class="k-chart-section">
          <div class="k-chart" :data-layout="layout">
            <p v-if="total === 0" class="k-chart-empty">{{ empty }}</p>

            <template v-else-if="layout === 'stack'">
              <div class="k-chart-stack" role="img" :aria-label="summary">
                <span
                  v-for="item in segments"
                  :key="item.label"
                  class="k-chart-segment"
                  :style="{ ...color(item), flexGrow: item.value }"
                  :title="tooltip(item) + ' · ' + percent(item.value)"
                />
              </div>
              <ul class="k-chart-legend">
                <li v-for="item in items" :key="item.label" :title="tooltip(item)">
                  <span class="k-chart-swatch" :style="color(item)" />
                  <span class="k-chart-label">{{ item.label }}</span>
                  <span class="k-chart-value">{{ item.value }} <small>{{ percent(item.value) }}</small></span>
                </li>
              </ul>
            </template>

            <ul v-else class="k-chart-bars">
              <li v-for="item in items" :key="item.label" :title="tooltip(item)">
                <span class="k-chart-label">
                  <img v-if="item.image" :src="item.image" alt="" class="k-chart-logo" loading="lazy">
                  <span>{{ item.label }}</span>
                </span>
                <span class="k-chart-track">
                  <span
                    class="k-chart-bar"
                    :style="{ ...color(item), width: (item.value / max) * 100 + '%' }"
                  />
                </span>
                <span class="k-chart-value">{{ item.value }} <small v-if="item.info">{{ item.info }}</small></span>
              </li>
            </ul>
          </div>
        </k-section>
      `
    }
  }
});
