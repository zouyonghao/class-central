export default function changeIconColor(currentColor, newColor, element) {
  const re = new RegExp("^icon-[a-z]*-" + currentColor + "$", "i");
  const currentIconClassNames = element.className.split(' ').filter(className => re.test(className));
  const newIconClassNames = currentIconClassNames.map(className => className.replace(currentColor, newColor));
  element.classList.remove(...currentIconClassNames);
  element.classList.add(...newIconClassNames);
}