import React from 'react';
import StoreDetailsSummary from './StoreDetailsSummary';

const StoreDetailsPanel = ({ storeDetails, onConfirm, onGenerateFixtures }) => {
    return (
        <div style={{
            width: 340,
            marginLeft: 32,
            border: "1px solid #ccc",
            borderRadius: 8,
            padding: 24,
            background: "#fff",
            boxShadow: "0 2px 8px #0001",
            height: "fit-content",
            position: "sticky",
            top: 16,
            display: 'flex',
            flexDirection: 'column',
            minHeight: 400
        }}>
            <h3 style={{ 
                margin: "0 0 16px 0", 
                fontSize: "1.1rem", 
                fontWeight: "bold",
                color: "#333"
            }}>
                Store Details
            </h3>
            <StoreDetailsSummary storeDetails={storeDetails} />
            <div style={{ marginTop: 'auto', display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                {onGenerateFixtures && (
                    <button
                        onClick={onGenerateFixtures}
                        style={{
                            padding: '8px 18px',
                            borderRadius: 6,
                            border: '1px solid #eee',
                            background: '#f3f3f3',
                            color: '#333',
                            fontWeight: 500,
                            cursor: 'pointer',
                        }}
                    >
                        Generuj fixtury
                    </button>
                )}
                <button
                    onClick={onConfirm}
                    style={{
                        padding: '8px 18px',
                        borderRadius: 6,
                        border: 'none',
                        background: '#10b981',
                        color: '#fff',
                        fontWeight: 600,
                        cursor: 'pointer',
                        boxShadow: '0 1px 4px #0001'
                    }}
                >
                    Zatwierdź
                </button>
            </div>
        </div>
    );
};

export default StoreDetailsPanel; 