import { configureStore } from '@reduxjs/toolkit'
import groups from './slices/groups'
import map from './slices/map'
import modal from './slices/modal'
import router from './slices/router'
import drawer from './slices/drawer'
import search from './slices/search'

export const store = configureStore({
  reducer: {
    groups,
    map,
    modal,
    router,
    drawer,
    search
  }
})
