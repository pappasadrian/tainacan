import Vue from 'vue';
import store from '../../admin/js/store/store';
import router from './roles-router';
import VTooltip from 'v-tooltip';
import { Snackbar, Modal } from 'buefy';

import { I18NPlugin } from './wp-i18n-plugin';

import RolesPage from '../roles.vue';

export default (element) => {

    // Vue Dev Tools!
    Vue.config.devtools = process && process.env && process.env.NODE_ENV === 'development';

    function renderTainacanRolePage() {

        // Gets the div with the content of the page
        let pageElement = element ? element : document.getElementById('tainacan-roles-app');

        // Mount only if the div exists and it is not already mounted
        if ( pageElement && pageElement.classList && !pageElement.classList.contains('has-mounted') ) {

            Vue.use(I18NPlugin);
            Vue.use(VTooltip, {
                defaultClass: 'tainacan-tooltip tooltip'
            });
            Vue.use(Snackbar);
            Vue.use(Modal);
            
            // Changing title of pages
            router.beforeEach((to, from, next) => {
                document.title = to.meta.title;
                if (next() != undefined)
                    next();
            });
            
            new Vue({
                el: '#tainacan-roles-app',
                store,
                router,
                render: h => h(RolesPage)
            });
        };
    };

    // This is rendered on the admin page.
    renderTainacanRolePage();
};

