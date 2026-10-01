export default function (el) {
  const tabLinks = Array.from(el.querySelectorAll('[data-tab-link]'))
  const tabContent = Array.from(el.querySelectorAll('[data-tab-content]'))
  const sliderRoot = el.querySelector('.post-tabs')
  const AUTOPLAY_DELAY = 6000
  const isHomepageCarousel = el.dataset.displayStyle === 'homepage'
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)')
  let currentIndex = 0
  let autoplayId = null
  let pointerStartX = null
  let resizeObserver = null

  if (!sliderRoot || !tabLinks.length || !tabContent.length) {
    return () => {}
  }

  const track = document.createElement('div')
  track.className = 'post-tabs__track'

  tabContent.forEach(content => {
    content.classList.add('carousel-slide')
    track.appendChild(content)
  })

  sliderRoot.appendChild(track)
  sliderRoot.classList.add('is-carousel')
  sliderRoot.setAttribute('aria-live', 'off')

  const activeLinkIndex = tabLinks.findIndex(link => link.classList.contains('active'))
  const activeContentIndex = tabContent.findIndex(content => content.classList.contains('active'))

  if (activeLinkIndex >= 0) {
    currentIndex = activeLinkIndex
  } else if (activeContentIndex >= 0) {
    currentIndex = activeContentIndex
  }

  function setActiveSlide (nextIndex) {
    currentIndex = (nextIndex + tabContent.length) % tabContent.length
    track.style.transform = `translateX(-${currentIndex * 100}%)`

    tabContent.forEach(function (content, index) {
      const isActive = index === currentIndex
      content.classList.toggle('active', isActive)
      content.setAttribute('aria-hidden', String(!isActive))
    })

    tabLinks.forEach(function (link, index) {
      const isActive = index === currentIndex
      link.classList.toggle('active', isActive)
      link.setAttribute('aria-pressed', String(isActive))
    })

    updateSliderHeight()
  }

  function updateSliderHeight () {
    if (!isHomepageCarousel) return

    const activeSlide = tabContent[currentIndex]

    if (activeSlide) {
      sliderRoot.style.height = `${activeSlide.getBoundingClientRect().height}px`
    }
  }

  function getIndexById (contentId) {
    return tabContent.findIndex(content => content.id === contentId)
  }

  function openTabs (e) {
    const btnTarget = e.currentTarget
    const index = getIndexById(btnTarget.dataset.index)

    if (index >= 0) {
      setActiveSlide(index)
      restartAutoplay()
    }
  }

  function nextSlide () {
    setActiveSlide(currentIndex + 1)
  }

  function startAutoplay () {
    if (tabContent.length <= 1 || autoplayId || prefersReducedMotion.matches || document.hidden) return
    autoplayId = setInterval(nextSlide, AUTOPLAY_DELAY)
  }

  function stopAutoplay () {
    if (!autoplayId) return
    clearInterval(autoplayId)
    autoplayId = null
  }

  function restartAutoplay () {
    stopAutoplay()
    startAutoplay()
  }

  function handleKeydown (event) {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return
    event.preventDefault()
    setActiveSlide(currentIndex + (event.key === 'ArrowRight' ? 1 : -1))
    restartAutoplay()
  }

  function handlePointerDown (event) {
    if (!event.isPrimary) return
    pointerStartX = event.clientX
    stopAutoplay()
  }

  function handlePointerUp (event) {
    if (pointerStartX === null || !event.isPrimary) return
    const distance = event.clientX - pointerStartX
    pointerStartX = null

    if (Math.abs(distance) >= 50) {
      setActiveSlide(currentIndex + (distance < 0 ? 1 : -1))
    }

    startAutoplay()
  }

  function handleVisibilityChange () {
    if (document.hidden) {
      stopAutoplay()
    } else {
      startAutoplay()
    }
  }

  setActiveSlide(currentIndex)

  tabLinks.forEach(function (link) {
    link.addEventListener('click', openTabs)
  })

  sliderRoot.addEventListener('pointerenter', stopAutoplay)
  sliderRoot.addEventListener('pointerleave', startAutoplay)
  sliderRoot.addEventListener('mouseenter', stopAutoplay)
  sliderRoot.addEventListener('mouseleave', startAutoplay)
  sliderRoot.addEventListener('keydown', handleKeydown)
  sliderRoot.addEventListener('pointerdown', handlePointerDown)
  sliderRoot.addEventListener('pointerup', handlePointerUp)
  sliderRoot.addEventListener('pointercancel', handlePointerUp)

  const controlsRoot = el.querySelector('.tab')
  if (controlsRoot) {
    controlsRoot.addEventListener('pointerenter', stopAutoplay)
    controlsRoot.addEventListener('pointerleave', startAutoplay)
    controlsRoot.addEventListener('mouseenter', stopAutoplay)
    controlsRoot.addEventListener('mouseleave', startAutoplay)
  }

  el.addEventListener('focusin', stopAutoplay)
  el.addEventListener('focusout', startAutoplay)
  document.addEventListener('visibilitychange', handleVisibilityChange)

  if (isHomepageCarousel && window.ResizeObserver) {
    resizeObserver = new window.ResizeObserver(updateSliderHeight)
    tabContent.forEach(content => resizeObserver.observe(content))
  }

  startAutoplay()

  return () => {
    tabLinks.forEach(function (link) {
      link.removeEventListener('click', openTabs)
    })

    sliderRoot.removeEventListener('pointerenter', stopAutoplay)
    sliderRoot.removeEventListener('pointerleave', startAutoplay)
    sliderRoot.removeEventListener('mouseenter', stopAutoplay)
    sliderRoot.removeEventListener('mouseleave', startAutoplay)
    sliderRoot.removeEventListener('keydown', handleKeydown)
    sliderRoot.removeEventListener('pointerdown', handlePointerDown)
    sliderRoot.removeEventListener('pointerup', handlePointerUp)
    sliderRoot.removeEventListener('pointercancel', handlePointerUp)

    if (controlsRoot) {
      controlsRoot.removeEventListener('pointerenter', stopAutoplay)
      controlsRoot.removeEventListener('pointerleave', startAutoplay)
      controlsRoot.removeEventListener('mouseenter', stopAutoplay)
      controlsRoot.removeEventListener('mouseleave', startAutoplay)
    }

    el.removeEventListener('focusin', stopAutoplay)
    el.removeEventListener('focusout', startAutoplay)
    document.removeEventListener('visibilitychange', handleVisibilityChange)

    if (resizeObserver) {
      resizeObserver.disconnect()
    }

    stopAutoplay()
  }
}
