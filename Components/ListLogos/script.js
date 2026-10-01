export default function (el) {
  const scroller = el.querySelector('[data-ref="scroller"]')
  const progress = el.querySelector('[data-ref="progress"]')
  const progressThumb = el.querySelector('[data-ref="progressThumb"]')
  let dragging = false
  let dragMoved = false
  let dragStartX = 0
  let dragStartScrollLeft = 0
  let progressDragging = false

  if (!scroller || !progress || !progressThumb) {
    return () => {}
  }

  function getMaxScroll () {
    return Math.max(0, scroller.scrollWidth - scroller.clientWidth)
  }

  function updateProgress () {
    const maxScroll = getMaxScroll()
    const isScrollable = maxScroll > 1

    el.dataset.scrollable = String(isScrollable)
    progress.disabled = !isScrollable

    if (isScrollable) {
      scroller.setAttribute('tabindex', '0')
    } else {
      scroller.removeAttribute('tabindex')
      progressThumb.style.removeProperty('width')
      progressThumb.style.transform = 'translateX(0)'
      return
    }

    const trackWidth = progress.clientWidth
    const thumbWidth = Math.max(48, trackWidth * (scroller.clientWidth / scroller.scrollWidth))
    const thumbTravel = trackWidth - thumbWidth
    const scrollProgress = scroller.scrollLeft / maxScroll

    progressThumb.style.width = `${thumbWidth}px`
    progressThumb.style.transform = `translateX(${thumbTravel * scrollProgress}px)`
  }

  function scrollFromProgressPointer (clientX) {
    const maxScroll = getMaxScroll()

    if (!maxScroll) return

    const bounds = progress.getBoundingClientRect()
    const thumbWidth = progressThumb.getBoundingClientRect().width
    const thumbTravel = bounds.width - thumbWidth
    const position = Math.min(thumbTravel, Math.max(0, clientX - bounds.left - thumbWidth / 2))

    scroller.scrollLeft = thumbTravel ? (position / thumbTravel) * maxScroll : 0
  }

  function handleScrollerPointerDown (event) {
    if (event.pointerType !== 'mouse' || event.button !== 0 || !getMaxScroll()) return

    dragging = true
    dragMoved = false
    dragStartX = event.clientX
    dragStartScrollLeft = scroller.scrollLeft
    el.dataset.dragging = 'true'
    scroller.setPointerCapture(event.pointerId)
  }

  function handleScrollerPointerMove (event) {
    if (!dragging) return

    const distance = event.clientX - dragStartX
    dragMoved = dragMoved || Math.abs(distance) > 4
    scroller.scrollLeft = dragStartScrollLeft - distance
  }

  function handleScrollerPointerUp (event) {
    if (!dragging) return

    dragging = false
    delete el.dataset.dragging

    if (scroller.hasPointerCapture(event.pointerId)) {
      scroller.releasePointerCapture(event.pointerId)
    }
  }

  function handleScrollerClick (event) {
    if (!dragMoved) return

    event.preventDefault()
    event.stopPropagation()
    dragMoved = false
  }

  function handleScrollerKeydown (event) {
    const step = Math.max(160, scroller.clientWidth * 0.75)
    let nextScrollLeft = null

    if (event.key === 'ArrowLeft') nextScrollLeft = scroller.scrollLeft - step
    if (event.key === 'ArrowRight') nextScrollLeft = scroller.scrollLeft + step
    if (event.key === 'Home') nextScrollLeft = 0
    if (event.key === 'End') nextScrollLeft = getMaxScroll()
    if (nextScrollLeft === null) return

    event.preventDefault()
    scroller.scrollTo({ left: nextScrollLeft, behavior: 'smooth' })
  }

  function handleProgressPointerDown (event) {
    if (!getMaxScroll()) return

    progressDragging = true
    progress.setPointerCapture(event.pointerId)
    scrollFromProgressPointer(event.clientX)
  }

  function handleProgressPointerMove (event) {
    if (!progressDragging) return

    scrollFromProgressPointer(event.clientX)
  }

  function handleProgressPointerUp (event) {
    if (!progressDragging) return

    progressDragging = false

    if (progress.hasPointerCapture(event.pointerId)) {
      progress.releasePointerCapture(event.pointerId)
    }
  }

  function handleProgressClick (event) {
    if (event.detail === 0) {
      scroller.scrollBy({ left: scroller.clientWidth * 0.75, behavior: 'smooth' })
    }
  }

  const resizeObserver = new window.ResizeObserver(updateProgress)

  scroller.addEventListener('scroll', updateProgress, { passive: true })
  scroller.addEventListener('pointerdown', handleScrollerPointerDown)
  scroller.addEventListener('pointermove', handleScrollerPointerMove)
  scroller.addEventListener('pointerup', handleScrollerPointerUp)
  scroller.addEventListener('pointercancel', handleScrollerPointerUp)
  scroller.addEventListener('click', handleScrollerClick, true)
  scroller.addEventListener('keydown', handleScrollerKeydown)
  progress.addEventListener('pointerdown', handleProgressPointerDown)
  progress.addEventListener('pointermove', handleProgressPointerMove)
  progress.addEventListener('pointerup', handleProgressPointerUp)
  progress.addEventListener('pointercancel', handleProgressPointerUp)
  progress.addEventListener('click', handleProgressClick)
  resizeObserver.observe(scroller)
  updateProgress()

  return () => {
    scroller.removeEventListener('scroll', updateProgress)
    scroller.removeEventListener('pointerdown', handleScrollerPointerDown)
    scroller.removeEventListener('pointermove', handleScrollerPointerMove)
    scroller.removeEventListener('pointerup', handleScrollerPointerUp)
    scroller.removeEventListener('pointercancel', handleScrollerPointerUp)
    scroller.removeEventListener('click', handleScrollerClick, true)
    scroller.removeEventListener('keydown', handleScrollerKeydown)
    progress.removeEventListener('pointerdown', handleProgressPointerDown)
    progress.removeEventListener('pointermove', handleProgressPointerMove)
    progress.removeEventListener('pointerup', handleProgressPointerUp)
    progress.removeEventListener('pointercancel', handleProgressPointerUp)
    progress.removeEventListener('click', handleProgressClick)
    resizeObserver.disconnect()
  }
}
