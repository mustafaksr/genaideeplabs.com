document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('geminiForm');
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      const prompt = document.getElementById('prompt').value;
      fetchGeminiResponse(prompt);
    });
  });
  
  function fetchGeminiResponse(prompt) {
    const data = {
      contents: [
        {
          parts: [
            {
              text: prompt
            }
          ]
        }
      ]
    };
  
    fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=AIzaSyAm6tCv6TaG5w5fW3hfjuyPb6GyhywwM-A', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
      const responseTextArea = document.getElementById('responsePrompt'); // Use responsePrompt textarea
      responseTextArea.value = data.candidates[0].content.parts[0].text;
    })
    .catch(error => console.error('Error:', error));
  }
  
