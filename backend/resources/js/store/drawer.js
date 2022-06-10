import axios from "axios"
const state = {
  isOpen: false,
  floors: []
};

const getters = {
  isOpen: (state) => state.isOpen,
  floors: (state) => state.floors,
};

const mutations = {
  open(state) {
    state.isOpen = true;
  },
  close(state) {
    state.isOpen = false;
  },
  set_floors(state, payload) {
    console.log(payload);
    state.floors = payload
  }
};

const actions = {
  async event(context, data) {
    console.log("event", data);
    if (data === false) {
      context.commit('close');
      return
    }
    context.commit('open');
    const floors = []
    const response = await axios.get('/api/rooms');
    response.data.forEach((room) => {
      // 着席者数のカウント
      let userNum = 0;
      room.sections.forEach((section) => {
        section.seats.forEach((seat) => {
          if (seat.user) {
            userNum += 1;
          }
        });
      });
      floors.push({
        id: room.id,
        name: room.name,
        userNum: userNum
      });
    });
    context.commit('set_floors', floors)
  },
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions,
};
