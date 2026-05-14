const stepVariants = {
    enter: (direction) => ({
        opacity: 0,
        x: direction > 0 ? 300 : -300,
        position: 'absolute',
        width: '100%',
        top: 0,
        left: 0,
        zIndex: 1
    }),
    center: {
        opacity: 1,
        x: 0,
        position: 'relative',
        width: '100%',
        zIndex: 2
    },
    exit: (direction) => ({
        opacity: 0,
        x: direction > 0 ? -300 : 300,
        position: 'absolute',
        width: '100%',
        top: 0,
        left: 0,
        zIndex: 1
    })
};

export default stepVariants; 