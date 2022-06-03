export const zeroPadding = (length, char = '0') => (target) => {
  return (Array(length + 1).join(char) + target).slice(-length);
};
