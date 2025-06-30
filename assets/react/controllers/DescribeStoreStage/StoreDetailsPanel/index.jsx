import React from 'react';
import StoreDetailsSummary from './StoreDetailsSummary';

const StoreDetailsPanel = ({storeDetails}) => {
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
                color: "#333"
            }}>
                Store Details
            </h3>
            {storeDetails && (
                <StoreDetailsSummary storeDetails={storeDetails}/>
            )}
        </div>
    );
};

export default StoreDetailsPanel;
