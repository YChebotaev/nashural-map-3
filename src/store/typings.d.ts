import { store } from './index'

import { Group, GeoJSONFeature, GeoJSON, GeoJSONCoordinates, Route } from '../typings.d'

export type RootState = ReturnType<typeof store.getState>

export type AppDispatch = typeof store.dispatch

export interface DrawerState {
  open: boolean
}

export interface ToggleDrawerPayload {
  on: boolean
}

export interface SelectedGroupsIndex {
  [key: string]: boolean
}

export interface GroupsIndex {
  [key: string]: GeoJSON
}

export interface GroupNamesIndex {
  [key: string]: string
}

export interface GroupsState {
  groups: GroupsIndex
  allGroups: Group[]
  selectedGroups: SelectedGroupsIndex
  activeGroups: string[]
  features: GeoJSONFeature[]
}

export interface ToggleGroupPayload {
  id: string
  on: boolean
}

export interface MapState {
  zoom: number
  center: GeoJSONCoordinates
  place: string|void
  isInline: boolean
  preselectAllGroups: boolean
}

export interface ChangeBoundsPayload {
  center: GeoJSONCoordinates
  zoom: number
}

export interface ModalPropsIndex {
  [key: string]: object
}

export interface ModalState {
  opened: string[]
  props: ModalPropsIndex
}

export interface ToggleModalPayload {
  on: boolean
  name: string
  props: object
}

export interface RouteInfo {
  show: boolean
  humanTime?: string,
  humanJamsTime?: string,
  humanLength?: string,
  humanFuel?: string
}

export interface RouterState {
  routes: Route[]
  open: boolean
  info: RouteInfo
}

export interface ToggleRouterPayload {
  on: boolean
}

export interface InitRoutesPayload {
  routes: Route[]
}

export interface ReorderRoutesPayload {
  fromIdx: number
  toIdx: number
}

export interface AppendRoutePayload {
  route: Route
}

export interface RemoveRoutePayload {
  index: number
}

export interface RouteSetCoordinatesPayload {
  index: number
  name: string
  coordinates: GeoJSONCoordinates
}

export interface SearchResult {
  groupId: string
  features: GeoJSONFeature[]
}

export interface GroupedFeaturesIndex {
  [key: string]: GeoJSONFeature[]
}

export interface SearchState {
  enabled: boolean
  query: string
  groupedFeatures: GroupedFeatures
}

export interface PerformSearchPayload {
  query: string
  results?: SearchResult[]
}

export interface ToggleEnabledPayload {
  on: boolean
}

export interface AddFeaturePayload {
  feature: GeoJSONFeature
}

export interface RemoveFeaturePayload {
  feature: GeoJSONFeature
}
