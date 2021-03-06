import {
  AddFeaturePayload,
  GroupNamesIndex,
  GroupsState,
  RemoveFeaturePayload,
  RootState,
  ToggleGroupPayload,
} from '../typings'
import { CustomProperties, GeoJSON, GeoJSONFeature, Group } from '../../typings'
import { PayloadAction, createAsyncThunk, createSlice } from '@reduxjs/toolkit'
import { getFeaturesURL, getGroupsURL } from '../../api/urls'

const isFeaturesSame = (featureA: GeoJSONFeature, featureB: GeoJSONFeature): boolean => {
  return (featureA.id === featureB.id) && ((featureA.properties as CustomProperties).group === (featureB.properties as CustomProperties).group)
}

const groupsSorter = (id1: string, id2: string): number => {
  return id1.localeCompare(id2)
}

export const allGroupsSelector = (state: RootState) => state.groups.allGroups

export const groupNamesByKeySelector = (state: RootState): GroupNamesIndex => {
  return state.groups.allGroups.reduce((groups: GroupNamesIndex, { id, name }: Group) => ({
    ...groups,
    [id]: name
  }), {})
}

export const groupsSelector = (state: RootState) => state.groups.groups

export const isGroupSelectedById = (id: string) => (state: RootState) => !!(state.groups.selectedGroups[id])

export const allFeaturesSelector = (state: RootState) => state.groups.features

export const activeGroupsSelector = (state: RootState) => state.groups.activeGroups

export const featureIsActiveByKeySelector = (key: string) => (state: RootState) => {
  for (let { id, properties } of state.groups.features) {
    const { group } = properties as CustomProperties
    if (key === `${group}-${id}`)
      return true
  }
  return false
}

export const selectGroupById = (groupId: string) => (state: RootState) => {
  for (let group of state.groups.allGroups) {
    if (group.id === groupId)
      return group
  }
  return undefined
}

export const fetchGroups = createAsyncThunk(
  'fetch-groups',
  async () => {
    const url = getGroupsURL()
    const resp = await fetch(url.toString())
    const data = await resp.json()
    return data.groups as Group[]
  }
)

export const fetchGroupById = createAsyncThunk(
  'fetch-group-by-id',
  async (groupId: string) => {
    const url = getFeaturesURL(groupId)
    const resp = await fetch(url.toString())
    return await resp.json() as GeoJSON
  }
)

export const fetchAndSelectAllGroups = createAsyncThunk(
  'featch-and-select-all-groups',
  async () => {
    const groupsUrl = getGroupsURL()
    let resp = await fetch(groupsUrl.toString())
    const data = await resp.json()
    const groups: Group[] = data.groups
    const groupedFeatures: Record<string, GeoJSON> = {}
    for (let group of data.groups) {
      const url = getFeaturesURL(group.id)
      resp = await fetch(url.toString())
      groupedFeatures[group.id] = await resp.json()
    }
    return {
      groups,
      groupedFeatures 
    }
  }
)

const mergeFeatures = (state: any, id: string) => {
  const features = state.features.slice(0)

  for (let newFeature of state.groups[id].features) {
    const index = features.findIndex((presentFeature: GeoJSONFeature) => isFeaturesSame(newFeature, presentFeature))
    if (index === -1) {
      features.push(newFeature)
    }
  }

  return features
}

const removeFeatures = (state: any, id: string) => {
  return state.features.filter((feature_: GeoJSONFeature) => {
    for (let feature of state.groups[id].features) {
      if (isFeaturesSame(feature, feature_)) {
        return false
      }
    }
    return true
  })
}

const initialState: GroupsState = {
  groups: {},
  allGroups: [],
  selectedGroups: {},
  activeGroups: [],
  features: []
}

const groupsSlice = createSlice({
  name: 'groups',
  initialState,
  reducers: {
    toggleGroup(state, action: PayloadAction<ToggleGroupPayload>) {
      const { id, on } = action.payload
      if (on) {
        state.activeGroups.push(id)
        state.activeGroups.sort(groupsSorter)
        state.selectedGroups[id] = true
      } else {
        const idx = state.activeGroups.findIndex(id_ => id === id_)
        state.activeGroups.splice(idx, 1)
        state.activeGroups.sort(groupsSorter)
        state.selectedGroups[id] = false
        state.features = removeFeatures(state, id)
      }
    },
    addFeature(state, action: PayloadAction<AddFeaturePayload>) {
      const { feature } = action.payload
      state.features.push(feature)
    },
    removeFeature(state, action: PayloadAction<RemoveFeaturePayload>) {
      const index = state.features.findIndex((feature: GeoJSONFeature) => {
        if (isFeaturesSame(feature, action.payload.feature)) {
          return true
        }
        return false
      })
      if (index >= -1) state.features.splice(index, 1)
    },
  },
  extraReducers: builder => {
    builder.addCase(fetchGroups.fulfilled, (state, action:PayloadAction<Group[]>) => {
      state.allGroups = action.payload
    })
    builder.addCase(fetchGroupById.fulfilled, (state, action:PayloadAction<GeoJSON>) => {
      state.groups[action.payload.metadata.id] = action.payload
      state.features = mergeFeatures(state, action.payload.metadata.id)
    })
    builder.addCase(fetchAndSelectAllGroups.fulfilled, (state, action) => {
      const { groups, groupedFeatures } = action.payload
      state.allGroups = groups
      for (let group of groups) {
        state.groups[group.id] = groupedFeatures[group.id]
        state.activeGroups.push(group.id)
        state.selectedGroups[group.id] = true
        const groupFeatures = groupedFeatures[group.id].features
        state.features.push(...groupFeatures)
      }
      state.activeGroups.sort(groupsSorter)
    })
  }
})

export const { toggleGroup, addFeature, removeFeature } = groupsSlice.actions

export default groupsSlice.reducer
