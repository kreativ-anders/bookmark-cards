/**
 * Panel views "Bookmarks" and "Users › Statistics" (site/blueprints/dashboard/*.yml).
 *
 * Only Kirby's own components: a header with optional buttons and the
 * blueprint's columns of native fields (stats, buttons) rendered by
 * k-model-form, like a site tab. All field props come with the view
 * (panelStatsView() in index.php), nothing is loaded afterwards.
 */
import { h, resolveComponent } from "vue";

panel.plugin("kreativ-anders/panel-stats", {
  components: {
    "k-dashboard-view": {
      props: {
        title: String,
        tab: Object,
        buttons: { type: Array, default: () => [] }
      },
      render() {
        const buttons = this.buttons.map((button) => ({ size: "sm", variant: "filled", ...button }));

        return h(resolveComponent("k-panel-inside"), { class: "k-dashboard-view" }, () => [
          h(resolveComponent("k-header"), null, {
            default: () => this.title,
            buttons: buttons.length ? () => h(resolveComponent("k-button-group"), { buttons }) : undefined
          }),
          h(resolveComponent("k-model-form"), {
            columns: this.tab?.columns ?? {},
            content: {},
            diff: {},
            lock: false,
            empty: "No statistics configured"
          })
        ]);
      }
    }
  }
});
