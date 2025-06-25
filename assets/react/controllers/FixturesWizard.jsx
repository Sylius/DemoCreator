import React, { useState } from "react";

const steps = [
  "Wywiad co do sklepu",
  "Generowanie fixtures",
  "Załadowanie fixtures do projektu Sylius-Standard"
];

const FixturesWizard = ({ onNext }) => {
  const [currentStep, setCurrentStep] = useState(0);

  const handleNext = () => {
    if (currentStep < steps.length - 1) {
      setCurrentStep(prev => prev + 1);
    } else {
      onNext();
    }
  };

  return (
    <div style={{ maxWidth: 400, margin: "0 auto" }}>
      <ul style={{ listStyle: "none", padding: 0 }}>
        {steps.map((label, index) => {
          const isActive = index === currentStep;
          const isCompleted = index < currentStep;
          return (
            <li
              key={index}
              style={{
                display: "flex",
                alignItems: "center",
                marginBottom: 20
              }}
            >
              <div
                style={{
                  width: 30,
                  height: 30,
                  borderRadius: "50%",
                  border: `2px solid ${isCompleted ? "green" : isActive ? "blue" : "#ccc"}`,
                  backgroundColor: isCompleted ? "green" : isActive ? "blue" : "#fff",
                  color: isCompleted || isActive ? "#fff" : "#000",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  marginRight: 10
                }}
              >
                {index + 1}
              </div>
              <span style={{ fontWeight: isActive ? "bold" : "normal" }}>{label}</span>
            </li>
          );
        })}
      </ul>
      <button
        onClick={handleNext}
        style={{
          padding: "8px 16px",
          border: "none",
          borderRadius: 4,
          backgroundColor: "green",
          color: "#fff",
          cursor: "pointer"
        }}
      >
        {currentStep < steps.length - 1 ? "Dalej" : "Zakończ"}
      </button>
    </div>
  );
};

export default FixturesWizard;
