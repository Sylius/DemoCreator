// assets/react/controllers/Dashboard.jsx
import React from 'react';
import { motion } from 'framer-motion';

const cards = [
    { title: 'Demo Creator', route: '/demo' },
    { title: 'Theme Creator', route: '/theme-creator' },
    { title: 'Fixture Creator', route: '/fixtures' },
    { title: 'Plugin Browser', route: '/plugins' },
];

export default function Dashboard() {
    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: { staggerChildren: 0.1 }
        }
    };
    const itemVariants = {
        hidden: { opacity: 0, y: 10 },
        visible: { opacity: 1, y: 0 }
    };

    return (
        <motion.div
            className="min-h-screen bg-gray-50 grid grid-cols-2 gap-4 justify-center content-center"
            variants={containerVariants}
            initial="hidden"
            animate="visible"
        >
            {cards.map(card => (
                <motion.div
                    key={card.route}
                    className="bg-white rounded-xl shadow-md p-4 w-36 h-36 flex flex-col items-center justify-center cursor-pointer hover:shadow-lg transition-shadow duration-200"
                    variants={itemVariants}
                    whileHover={{ scale: 1.05 }}
                    whileTap={{ scale: 0.95 }}
                    onClick={() => window.location.href = card.route}
                >
                    <div className="text-teal-600 text-2xl mb-2">🚀</div>
                    <h2 className="text-base font-medium text-gray-800 text-center">{card.title}</h2>
                </motion.div>
            ))}
        </motion.div>
    );
}
