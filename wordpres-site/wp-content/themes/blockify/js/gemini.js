document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('geminiForm');
    const dict_history = new Map();
    
     if (currentUser) {
        console.log('Succes for getting user info.');

      /* console.log('User ID:', currentUser.ID);
        console.log('User Login:', currentUser.user_login);
        console.log('User Email:', currentUser.user_email);
        console.log('Display Name:', currentUser.display_name); */
    } else {
        console.log('No user is logged in.');
    }
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const prompt = document.getElementById('prompt').value;
        fetchGeminiResponse(prompt);
    });

    const copyButton = document.getElementById('copyButton');
    copyButton.addEventListener('click', function(event) {
        event.preventDefault();
        const responseText = document.getElementById('responsePrompt').value;
        console.log('Copy button clicked. Text to copy:', responseText);
        window.focus();
        copyToClipboard(responseText);
    });

    const downloadButton = document.getElementById('download_history');
    downloadButton.addEventListener('click', function(event) {
        event.preventDefault();
        downloadHistoryAsJSON();
    });

    function fetchGeminiResponse(prompt) {
        const data = {
            "prompt": prompt,
            "user_email": currentUser.user_email
        };

        // Fetch latest hashed application password from WordPress using AJAX
        jQuery.post(customAppPasswords.ajaxurl, {
            action: 'get_latest_application_password',
            user_id: currentUser.ID,
            // Include nonce for security
            _ajax_nonce: customAppPasswords.nonce
        }, function(response) {
            try {
                if (response.success) {
                    data.application_password = response.data.application_password || "";
                   /* console.log("application_password:", data.application_password);*/
                } else {
                    console.error('Failed to fetch hashed application password:', response.data.message);
                    data.application_password = ""; // Set to empty string if not found
                }

                // Proceed with your fetch request
                fetch(`https://backendgemini.genaideeplabs.com/generate_content`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    const responseTextArea = document.getElementById('responsePrompt');
                    const text = data.response;
                    responseTextArea.value = '';
                    printTextSlowly(responseTextArea, text);
                    updateHistoryColumn(prompt, text);
                })
                .catch(error => console.error('Error fetching Gemini response:', error));
            } catch (error) {
                console.error('An unexpected error occurred:', error);
                data.application_password = ""; // Ensure it's set to an empty string in case of error
            }
        });
    }

    function printTextSlowly(textArea, text) {
        let index = 0;
        textArea.value = '';
        const printNextCharacter = () => {
            if (index < text.length) {
                textArea.value += text.charAt(index);
                index++;
                setTimeout(printNextCharacter, 3);
            }
        };
        printNextCharacter();
    }

    function updateHistoryColumn(prompt, response) {
        const historyColumn = document.getElementById('history-column');
    
        // Check if prompt already exists in the dict_history
        let key = prompt;
        let counter = 1;
        while (dict_history.has(key)) {
            key = `${prompt} #${counter}`; // Append counter to prompt
            counter++;
        }
    
        const historyItem = document.createElement('div');
        historyItem.textContent = `Prompt: ${prompt}`;
        dict_history.set(key, response); // Use the updated key
    
        historyItem.style.border = '1px solid #000';
        historyItem.style.borderRadius = '8px';
        historyItem.style.padding = '3px';
        historyItem.style.cursor = 'pointer';
        historyItem.style.transition = 'background-color 0.3s';
        historyItem.addEventListener('mouseover', function() {
            historyItem.style.backgroundColor = '#022e75';
        });
        historyItem.addEventListener('mouseout', function() {
            historyItem.style.backgroundColor = '';
        });
    
        historyItem.addEventListener('click', function() {
            const response = dict_history.get(key);
            document.getElementById('responsePrompt').value = response;
            const promptInput = document.getElementById('prompt');
            promptInput.value = prompt;
        });
    
        if (historyColumn.firstChild) {
            historyColumn.insertBefore(historyItem, historyColumn.firstChild);
        } else {
            historyColumn.appendChild(historyItem);
        }
    }
    

    function copyToClipboard(text) {
        window.focus();
        navigator.clipboard.writeText(text).then(function() {
            console.log('Copied to clipboard:', text);
            alert('Copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }

    function downloadHistoryAsJSON() {
        const historyObject = Object.fromEntries(dict_history);
        const json = JSON.stringify(historyObject, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'history.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
});
