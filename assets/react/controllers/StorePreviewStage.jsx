import React, { useEffect } from 'react';

const StorePreviewStage = ({ isReady, error, onGenerate }) => {
    useEffect(() => {
        if (!isReady && typeof onGenerate === 'function') {
            onGenerate();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    if (error) {
        return <div className="text-red-600 mb-4">Error generating store: {error}</div>;
    }
    if (!isReady) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[40vh]">
                <div className="mb-4">Generating your store, please wait...</div>
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-teal-600"></div>
            </div>
        );
    }
    return (
        <div>
            <div className="mb-4">Your store is ready! You can preview and edit it below:</div>
            <iframe
                src="http://localhost:8000/"
                style={{ width: '100%', height: '70vh', border: '1px solid #ddd', borderRadius: 8 }}
                title="Sylius Store Preview"
            />
        </div>
    );
};

export default StorePreviewStage; 