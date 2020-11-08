import React, { FC, useCallback } from 'react'
import { useSelector } from 'react-redux'
import Media from 'react-media'
import { Placemark } from 'react-yandex-maps'

import { useDispatch } from '../../hooks/useDispatch'
import { DESKTOP, MOBILE } from '../../constants/mediaQueries'
import { changeBounds, mapStateSelector } from '../../store/slices/map'
import { allFeaturesSelector } from '../../store/slices/groups'
import { toggleModal } from '../../store/slices/modal'
import { pointsSelector } from '../../store/slices/router'
import { MobileMap } from './MobileMap'
import { DesktopMap } from './DesktopMap'

import { MapProps } from './typings.d'
import { GeoJSONCoordinates } from '../../typings.d'

import "./universal.css"
import "./desktop.css"
import "./mobile.css"

export const Map: FC<MapProps> = () => {
  const dispatch = useDispatch()
  const { center, zoom } = useSelector(mapStateSelector)
  const features = useSelector(allFeaturesSelector)
  const points = useSelector(pointsSelector)

  const handlePlacemarkClick = useCallback((e: any, coordinates: GeoJSONCoordinates) => {
    const { iconCaption, previewSrc, articleHref } = e.get('target').properties.getAll()
    dispatch(toggleModal({
      on: true,
      name: 'placemark',
      props: {
        title: iconCaption,
        src: previewSrc,
        href: articleHref,
        coordinates
      }
    }))
  }, [dispatch])

  const renderPlacemark = ({ id, geometry, properties }: any) => {
    return <Placemark
      key={`${properties.group}-${id}`}
      geometry={geometry}
      properties={properties}
      options={{
        preset: 'islands#circleIcon',
        iconColor: '#0074a0'
      }}
      onClick={(e: any) => handlePlacemarkClick(e, geometry.coordinates)}
    />
  }

  const handleBoundsChange = useCallback(e => {
    dispatch(changeBounds({
      center: e.get('newCenter'),
      zoom: e.get('newZoom')
    }))
  }, [dispatch])

  return (
    <Media queries={{ desktop: DESKTOP, mobile: MOBILE }} defaultMatches={{ desktop: true }}>{({ mobile, desktop }) => {
      if (mobile) {
        return <MobileMap
          center={center}
          zoom={zoom}
          features={features}
          points={points}
          renderPlacemark={renderPlacemark}
          onBoundsChange={handleBoundsChange}
        />
      }

      if (desktop) {
        return <DesktopMap
          center={center}
          zoom={zoom}
          features={features}
          points={points}
          renderPlacemark={renderPlacemark}
          onBoundsChange={handleBoundsChange}
        />
      }

      return null
    }}</Media>
  )
}