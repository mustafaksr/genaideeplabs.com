## Create Ubuntu Image Manually for Automl
You need to create a VM first, thrn SSH into this VM.
1. Install conda and requirements.txt:

    ```bash
    sudo apt-get update
    
    #install conda
    chmod +x Anaconda3-2024.02-1-Linux-x86_64.sh 
    ./Anaconda3-2024.02-1-Linux-x86_64.sh 

    nano .bashrc 
    ###---------------------
    # >>> conda initialize >>>
    # !! Contents within this block are managed by 'conda init' !!
    __conda_setup="$('/home/mustafakeser/anaconda3/bin/conda' 'shell.bash' 'hook' 2> /dev/null)"
    if [ $? -eq 0 ]; then
        eval "$__conda_setup"
    else
        if [ -f "/home/mustafakeser/anaconda3/etc/profile.d/conda.sh" ]; then
            . "/home/mustafakeser/anaconda3/etc/profile.d/conda.sh"
        else
            export PATH="/home/mustafakeser/anaconda3/bin:$PATH"
        fi
    fi
    unset __conda_setup
    # <<< conda initialize <<<
    ###---------------------
    source .bashrc 

    #install requirements
    pip3 install -r requirements.txt 



    ```
2. create startup service:

    ```bash
    sudo nano /etc/systemd/system/my_script.service
    ```

    add with nano:
    
    ```bash
    [Unit]
    Description=My Startup Script

    [Service]
    ExecStart=/home/mustafakeser/startup_script_ubuntu.sh
    User=mustafakeser

    [Install]
    WantedBy=default.target

    #Ctrl+s , Ctrl+x
    ```

3. start startup service:

    ```bash
    sudo systemctl enable my_script.service
    sudo systemctl start my_script.service
    sudo systemctl status my_script.service
    ```

4. stop vm

5. create image
