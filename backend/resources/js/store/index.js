import Vue from 'vue';
import Vuex from 'vuex';
Vue.use(Vuex);

import alert from './alert';
import auth from './auth';
import dialog from './dialog';
import drawer from './drawer';


const store = new Vuex.Store({
  modules: {
    alert,
    auth,
    dialog,
    drawer,
  },
});

export default store;
