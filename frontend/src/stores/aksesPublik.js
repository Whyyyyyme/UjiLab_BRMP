import { defineStore } from 'pinia'

export const useAksesPublikStore = defineStore('aksesPublik', {
  state: () => ({
    token: null, // in-memory only, tidak di-persist ke localStorage
    pengujianId: null,
    nomorPengujian: null,
    skmFilled: false,
  }),
  getters: {
    hasAkses: (state) => !!state.token,
  },
  actions: {
    setAkses(token, pengujianId, nomorPengujian, skmFilled = false) {
      this.token = token
      this.pengujianId = pengujianId
      this.nomorPengujian = nomorPengujian
      this.skmFilled = skmFilled
    },
    setSkmFilled(status) {
      this.skmFilled = status
    },
    clearAkses() {
      this.token = null
      this.pengujianId = null
      this.nomorPengujian = null
      this.skmFilled = false
    }
  }
})
