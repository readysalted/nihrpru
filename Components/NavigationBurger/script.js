import { disableBodyScroll, enableBodyScroll } from 'body-scroll-lock'
import delegate from 'delegate-event-listener'
import { buildRefs } from '@/assets/scripts/helpers.js'

export default function (el) {
  let isMenuOpen
  const refs = buildRefs(el)
  const navigationHeight = parseInt(window.getComputedStyle(el).getPropertyValue('--navigation-height')) || 0

  const isDesktopMediaQuery = window.matchMedia('(min-width: 1200px)')
  isDesktopMediaQuery.addEventListener('change', onBreakpointChange)

  const menuButtonClickDelegate = delegate('[data-ref="menuButton"]', onMenuButtonClick)
  el.addEventListener('click', menuButtonClickDelegate)

  onBreakpointChange()

  function onMenuButtonClick (e) {
    isMenuOpen = !isMenuOpen
    refs.menuButton.setAttribute('aria-expanded', isMenuOpen)

    if (isMenuOpen) {
      el.setAttribute('data-status', 'menuIsOpen')
      disableBodyScroll(refs.menu)
    } else {
      el.removeAttribute('data-status')
      document.querySelectorAll('#menu-container .active').forEach((e) => {
        e.classList.remove('active')
      })
      enableBodyScroll(refs.menu)
    }
  }

  function onBreakpointChange () {
    if (!isDesktopMediaQuery.matches) {
      setScrollPaddingTop()
    }
  }

  function setScrollPaddingTop () {
    const scrollPaddingTop = document.getElementById('wpadminbar')
      ? navigationHeight + document.getElementById('wpadminbar').offsetHeight
      : navigationHeight
    document.documentElement.style.scrollPaddingTop = `${scrollPaddingTop}px`
  }

  return () => {
    isDesktopMediaQuery.removeEventListener('change', onBreakpointChange)
    el.removeEventListener('click', menuButtonClickDelegate)
  }
}

const removeClassesExcept = (selectors, className, exceptElement) => {
  selectors.forEach(selector => {
    document.querySelectorAll(selector).forEach(item => {
      if (item !== exceptElement) {
        item.classList.remove(className)
      }
    })
  })
}

document.querySelectorAll('.arrow-icon').forEach(item => {
  item.addEventListener('click', e => {
    const target = e.currentTarget
    const submenuNumber = target.getAttribute('data-submenu')
    const subSubmenuNumber = target.getAttribute('data-sub-submenu')

    if (submenuNumber) {
      const activeSubmenu = document.getElementById(`submenu-${submenuNumber}`)
      const activeArrow = document.getElementById(`submenu-arrow-${submenuNumber}`)
      const activeLink = document.getElementById(`submenu-link-${submenuNumber}`)

      removeClassesExcept(['.submenu'], 'active', activeSubmenu)
      removeClassesExcept(['.parent-arrow'], 'active', activeArrow)
      removeClassesExcept(['.parent-link'], 'active', activeLink)
      removeClassesExcept(['.sub-submenu', '.child-arrow', '.child-link'], 'active', null)

      activeSubmenu.classList.toggle('active')
      activeArrow.classList.toggle('active')
      activeLink.classList.toggle('active')
    }

    if (subSubmenuNumber) {
      const activeSubSubmenu = document.getElementById(`sub-submenu-${subSubmenuNumber}`)
      const activeSubArrow = document.getElementById(`sub-submenu-arrow-${subSubmenuNumber}`)
      const activeSublink = document.getElementById(`sub-submenu-link-${subSubmenuNumber}`)

      activeSubSubmenu.classList.toggle('active')
      activeSubArrow.classList.toggle('active')
      activeSublink.classList.toggle('active')
    }
  })
})
