import numpy as np 
import pandas as pd 
import os, sys, uuid
import fire
from autogluon.tabular import TabularDataset, TabularPredictor
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, balanced_accuracy_score, matthews_corrcoef, roc_auc_score, log_loss, precision_score, recall_score, f1_score
from sklearn.metrics import mean_squared_error, mean_absolute_error, median_absolute_error, r2_score
from sklearn.metrics import average_precision_score
from sklearn.metrics import precision_recall_curve, mean_absolute_percentage_error
from sklearn.model_selection import KFold
from sklearn.metrics import f1_score
from sklearn.preprocessing import OrdinalEncoder, StandardScaler, OneHotEncoder 
from sklearn.pipeline import Pipeline
from sklearn.impute import SimpleImputer,KNNImputer
from sklearn.compose import ColumnTransformer
from sklearn.model_selection import train_test_split, KFold, StratifiedKFold , GroupKFold ,StratifiedGroupKFold
from scipy.stats import rankdata
from openfe import OpenFE, transform,tree_to_formula
from IPython.display import display
import subprocess
import logging
from datetime import datetime  # Import datetime module
import json
from google.cloud import pubsub_v1
from time import sleep

# Create a datetime object for the current time
now = datetime.now()
# Format the datetime object into a string with the desired format
TIMESTAMP_LOG = now.strftime("%Y-%m-%d-%H-%M-%S")



def train_fn(train_data,test_data,label,models_name,eval_metric,prediction,problem_type,split_type_,PREPROCESS,basic_features_engineering,PRESET,time_limit,hypertune,hyperparameter_num_trials,load_predictor=False,pubsub_topic=None,project_id=None,uuid_str=None,load_predictor_path=None):
   
    time_limit = min(time_limit, 30)


    LOG_FILENAME = f"{TIMESTAMP_LOG}-{label}-{models_name}-{eval_metric}-{problem_type}-{split_type_}-{uuid_str}.log"

    if pubsub_topic is not None:
        # PUBSUB Topic
        publisher = pubsub_v1.PublisherClient()
        topic_path = publisher.topic_path(project_id, pubsub_topic)

        print("\n\n\nStarting logging to Pub/Sub.")

        def publish_to_pubsub(producer, topic, log_level=logging.INFO):
            def write(buf):
                for line in buf.rstrip().splitlines():
                    if "/home/" not in line and "/media/" not in line:
                        if  f"{project_id}" in line:
                            timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                            log_message = f"{timestamp}: {line.split(str(project_id))[1]}"
                            future = producer.publish(topic, log_message.encode("utf-8"))
                            future.result()  # Ensure the message is published before continuing
                            # Append to log file
                            with open(LOG_FILENAME, "a") as f:
                                f.write(log_message + '\n')
                        else:
                            timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                            log_message = f"{timestamp}: {line}"
                            future = producer.publish(topic, log_message.encode("utf-8"))
                            future.result()  # Ensure the message is published before continuing
                            # Append to log file
                            with open(LOG_FILENAME, "a") as f:
                                f.write(log_message + '\n')

            return write

        # Set up logging
        logging.basicConfig(stream=sys.stdout, level=logging.INFO)
        logger = logging.getLogger()
        
        # Redirect stdout and stderr
        sys.stdout.write = publish_to_pubsub(publisher, topic_path, logging.INFO)
        sys.stderr.write = publish_to_pubsub(publisher, topic_path, logging.ERROR)
        
        print("Started logging to Pub/Sub.")




    #train dataframe
    train_data = TabularDataset(train_data).drop(columns=["id"])
    #test dataframe
    test_data = TabularDataset(test_data).drop(columns=["id"])


    # adjust metrics for your needs
    binary_metrics = {
        "accuracy": accuracy_score,
        "acc": accuracy_score,
        "balanced_accuracy": balanced_accuracy_score,
        "mcc": matthews_corrcoef,
        "roc_auc_ovo_macro": roc_auc_score,
        "log_loss": log_loss,
        "nll": log_loss,
        "pac": precision_recall_curve,
        "pac_score": average_precision_score,
        "quadratic_kappa": None,  # No direct equivalent in sklearn
        "roc_auc": roc_auc_score,
        "average_precision": average_precision_score,
        "precision": precision_score,
        "precision_macro": precision_score,
        "precision_micro": precision_score,
        "precision_weighted": precision_score,
        "recall": recall_score,
        "recall_macro": recall_score,
        "recall_micro": recall_score,
        "recall_weighted": recall_score,
        "f1": f1_score,
        "f1_macro": f1_score,
        "f1_micro": f1_score,
        "f1_weighted": f1_score
    }

    multiclass_metrics = {
        "accuracy": accuracy_score,
        "acc": accuracy_score,
        "balanced_accuracy": balanced_accuracy_score,
        "mcc": matthews_corrcoef,
        "roc_auc_ovo_macro": roc_auc_score,
        "log_loss": log_loss,
        "nll": log_loss,
        "pac": precision_recall_curve,
        "pac_score": average_precision_score,
        "quadratic_kappa": None,  # No direct equivalent in sklearn
        "precision_macro": precision_score,
        "precision_micro": precision_score,
        "precision_weighted": precision_score,
        "recall_macro": recall_score,
        "recall_micro": recall_score,
        "recall_weighted": recall_score,
        "f1_macro": f1_score,
        "f1_micro": f1_score,
        "f1_weighted": f1_score
    }

    regression_metrics = {
        "r2": r2_score,
        "mean_squared_error": mean_squared_error,
        "mse": mean_squared_error,
        "root_mean_squared_error": mean_squared_error,  # No direct equivalent in sklearn
        "rmse": mean_squared_error,
        "mean_absolute_error": mean_absolute_error,
        "mae": mean_absolute_error,
        "median_absolute_error": median_absolute_error,
        "mean_absolute_percentage_error": mean_absolute_percentage_error,  # No direct equivalent in sklearn
        "mape": mean_absolute_percentage_error,  # No direct equivalent in sklearn
        "symmetric_mean_absolute_percentage_error": None,  # No direct equivalent in sklearn
        "smape": None,  # No direct equivalent in sklearn
        "spearmanr": None,  # No direct equivalent in sklearn
        "pearsonr": None  # No direct equivalent in sklearn
    }
    all_metrics = {**binary_metrics, **multiclass_metrics, **regression_metrics}

    # define categorical and numerical features
    categorical_features = [x for x in train_data.columns if train_data[x].dtype=="O" and x!=label]


    numeric_features = [x for x in train_data.columns if train_data[x].dtype!="O" and x!= "id" and x!=label]


    # Preprocessing for numerical data
    numeric_transformer = Pipeline(steps=[
        ('imputer', KNNImputer(n_neighbors=12)),  # Replace missing values with the mean
        ('scaler', StandardScaler())  # Scale features to have zero mean and unit variance
    ])

    # Preprocessing for categorical data
    categorical_transformer = Pipeline(steps=[
        ('imputer', SimpleImputer(strategy='most_frequent')),  # Replace missing values with the most frequent value
        # ('onehot', OneHotEncoder(handle_unknown='ignore')),  # One-hot encode categorical variables
        ('ordinal', OrdinalEncoder(
                                handle_unknown="use_encoded_value", unknown_value=-1, encoded_missing_value=-1
                                ) )  

    ])

    # Combine preprocessing for numerical and categorical data
    preprocessor = ColumnTransformer(
        transformers=[
            ('num', numeric_transformer, numeric_features),
            ('cat', categorical_transformer, categorical_features)
        ])

    if PREPROCESS:
        train_data_Xt = preprocessor.fit_transform(train_data)
        test_data_Xt = preprocessor.transform(test_data)
        try:
            train_data_Xt_df = pd.DataFrame(train_data_Xt.A,columns=[f"f_{i}" for i in range(train_data_Xt.shape[1])])
            test_data_Xt_df = pd.DataFrame(test_data_Xt.A,columns=[f"f_{i}" for i in range(test_data_Xt.shape[1])])
        except:
            train_data_Xt_df = pd.DataFrame(train_data_Xt,columns=[f"f_{i}" for i in range(train_data_Xt.shape[1])])
            test_data_Xt_df = pd.DataFrame(test_data_Xt,columns=[f"f_{i}" for i in range(test_data_Xt.shape[1])])
        assert train_data_Xt_df.shape[1] == test_data_Xt_df.shape[1]
        train_data_Xt_df[label] = train_data[label].values
        train_data = train_data_Xt_df.copy()
        test_data = test_data_Xt_df.copy()    

    if basic_features_engineering:
        def feat_eng(df):
            numeric_features = [x for x in df.columns if df[x].dtype!="O" and x!= "id" and x!=label]
            print('computing f_sum')
            df['fsum'] = df[numeric_features].sum(axis=1) # for tree models
            print('computing f_std')
            df['f_std']  = df[numeric_features].std(axis=1)
            print('computing f_mean')
            df['f_mean'] = df[numeric_features].mean(axis=1)
            print('computing f_max')
            df['f_max']  = df[numeric_features].max(axis=1)
            print('computing f_min')
            df['f_min']  = df[numeric_features].min(axis=1)

            print('computing f_median')
            df['f_median'] = df[numeric_features].median(axis=1)
            print('computing f_25th')
            df['f_25th'] = df[numeric_features].quantile(0.25, axis=1)
            print('computing f_75th')
            df['f_75th'] = df[numeric_features].quantile(0.75, axis=1)
            print('computing f_skew')
            df['f_skew'] = df[numeric_features].skew(axis=1)
            print('computing f_kurt')
            df['f_kurt'] = df[numeric_features].kurt(axis=1)
            df['special1'] = df['fsum'].isin(np.arange(72, 76)) # for linear models
            for i in range(10,100,10):
                print(f'computing f_{i}th')
                df[f'f_{i}th'] = df[numeric_features].quantile(i/100, axis=1)
            print('computing f_harmonic')
            df['f_harmonic'] = len(numeric_features) / df[numeric_features].apply(lambda x: (1/x).mean(), axis=1)
            print('computing f_geometric')
            df['f_geometric'] = df[numeric_features].apply(lambda x: x.prod()**(1/len(x)), axis=1)
            print('computing f_zscore')
            df['f_zscore'] = df[numeric_features].apply(lambda x: (x - x.mean()) / x.std(), axis=1).mean(axis=1)
            print('computing Coefficient of Variation ')
            df['f_cv'] = df[numeric_features].std(axis=1) / df[numeric_features].mean(axis=1)
            print('computing f_Quantile Coefficients of Skewness_75')
            df['f_Quantile Coefficients of Skewness_75'] = (df[numeric_features].quantile(0.75, axis=1) - df[numeric_features].mean(axis=1)) / df[numeric_features].std(axis=1)
            print('computing f_Quantile Coefficients of Skewness_25')
            df['f_Quantile Coefficients of Skewness_25'] = (df[numeric_features].quantile(0.25, axis=1) - df[numeric_features].mean(axis=1)) / df[numeric_features].std(axis=1)
            print('computing f_2ndMoment')
            df['f_2ndMoment'] = df[numeric_features].apply(lambda x: (x**2).mean(), axis=1)
            print('computing f_3rdMoment')
            df['f_3rdMoment'] = df[numeric_features].apply(lambda x: (x**3).mean(), axis=1)
            print('computing f_entropy')
            df['f_entropy'] = df[numeric_features].apply(lambda x: -1*(x*np.log(x)).sum(), axis=1)
            print('computing f_mad') #probably has negative impact
            df['f_mad'] = df[numeric_features].apply(lambda x: (x - x.median()).abs().median(), axis=1)
            print('computing f_iqr') #probably has negative impact
            df['f_iqr'] = df[numeric_features].quantile(0.75, axis=1) - df[numeric_features].quantile(0.25, axis=1)
            print('computing f_mode')
            df['f_mode'] = df[numeric_features].mode(axis=1)[0]
            return df
        print("_"*100)
        print("\nFeature Engineering:")   
        train_data = feat_eng(train_data.sample(frac=0.1))
        test_data = feat_eng(test_data.sample(frac=0.1))

    def train_split_types(type_=None,group=None,task=None):
        n_split=5
        random_state=42
        train_data["id"] = " "
        
        if type_== "train_test":
            try:
                X_train, X_test = train_test_split(
            train_data.drop(columns=["id"]), test_size=0.1, random_state=random_state, stratify=train_data[label])
            except:
                X_train, X_test = train_test_split(
            train_data.drop(columns=["id"]), test_size=0.1, random_state=random_state)
        elif type_== "KFold":
            for train_idx, val_idx in KFold(n_splits=n_split,  shuffle=True, random_state=random_state).split(train_data.drop(columns=["id"]),train_data[label]):
                X_train, X_test = train_data.iloc[train_idx], train_data.iloc[val_idx]
                break
                
        elif type_== "StratifiedKFold":
            if task =="regression":
                for train_idx, val_idx in StratifiedKFold(n_splits=n_split,  shuffle=True, random_state=random_state).split(train_data.drop(columns=["id"]),pd.Series(rankdata(train_data[label])).astype(int)):
                    X_train, X_test = train_data.iloc[train_idx], train_data.iloc[val_idx]
                    break
            elif task=="binary" or task=="multiclass":
                for train_idx, val_idx in StratifiedKFold(n_splits=n_split,  shuffle=True, random_state=random_state).split(train_data.drop(columns=["id"]),train_data[label]):
                    X_train, X_test = train_data.iloc[train_idx], train_data.iloc[val_idx]
                    break
                
        # elif type_== "GroupKFold":
            
        #     for train_idx, val_idx in GroupKFold(n_splits=n_split).split(train_data.drop(columns=["id"]),train_data[label],groups=train_data[group]):
        #         X_train, X_test = train_data.iloc[train_idx], train_data.iloc[val_idx]
        #         break
                
        # elif type_== "StratifiedGroupKFold":
        #     if task =="regression":
        #         for train_idx, val_idx in StratifiedGroupKFold(n_splits=n_split,  shuffle=True, random_state=random_state).split(train_data.drop(columns=["id"]),pd.Series(rankdata(train_data[label])).astype(int)):
        #             X_train, X_test = train_data.iloc[train_idx], train_data.iloc[val_idx]
        #             break
        #     elif task=="binary" or task=="multiclass":
        #         for train_idx, val_idx in StratifiedGroupKFold(n_splits=n_split,  shuffle=True, random_state=random_state).split(train_data.drop(columns=["id"]),train_data[label]):
        #             X_train, X_test = train_data.iloc[train_idx], train_data.iloc[val_idx]
        #             break
                
        return X_train, X_test

    X_train, X_test = train_split_types(type_=split_type_, group=None, task=problem_type )

    print("_"*100)
    print("\nTraining:")   
    # def ag():
    hyperparameter_tune_kwargs = {  
        'num_trials': hyperparameter_num_trials,
        'scheduler' : 'local',
        'searcher': 'auto',
    }

    MODEL_FILENAME = models_name
    gcs_path = f"gs://{project_id}"
    gcs_path_model = f"gs://{uuid_str}"
    gcs_path_data = f"gs://{uuid_str}/data"
    gcs_path_logs = f"gs://{project_id}/logs"
    

    if load_predictor:
        loaded_model_name = load_predictor_path.split("/")[-1]
        subprocess.run(["gsutil", "cp", "-r", load_predictor_path, "."], check=True)
        predictor = TabularPredictor.load(loaded_model_name)

    else:
        predictor = TabularPredictor(
                                label = label,
                                eval_metric = eval_metric,
                                problem_type = problem_type ,
                                path = models_name
                                )

        if hypertune:
            predictor.fit(X_train,
                        time_limit = time_limit,
                            hyperparameter_tune_kwargs=hyperparameter_tune_kwargs,
                        presets = PRESET,
                        save_space = True,
                        keep_only_best = False,
                        )
        else:
                predictor.fit(X_train,
                        time_limit = time_limit,
                            #   hyperparameter_tune_kwargs=hyperparameter_tune_kwargs,
                        presets = PRESET,
                        save_space = True,
                        keep_only_best = False,
                        )

    evals = predictor.evaluate(X_test)
    print("_"*100)
    print("\nEvals:")
    display(evals)

    LB = predictor.leaderboard(X_test)
    print("_"*100)
    print("\nLeaderboard:")
    display(LB)

    feature_importance = predictor.feature_importance(X_test)
    print("_"*100)
    print("\nFeature Importance:")
    print(feature_importance)
    try:
        if problem_type=="binary" and prediction=="soft":
            test_preds = predictor.predict_proba(test_data )
            test_preds.to_csv(f"{gcs_path_data}/test-preds-{uuid_str}.csv",index=False)

        elif prediction=="hard":
            test_preds = predictor.predict(test_data )
            test_preds.to_csv(f"{gcs_path_data}/test-preds-{uuid_str}.csv",index=False)
        else:
            test_preds = predictor.predict(test_data )
            test_preds.to_csv(f"{gcs_path_data}/test-preds-{uuid_str}.csv",index=False)

    except:
        test_preds = predictor.predict(test_data )
        test_preds.to_csv(f"{gcs_path_data}/test-preds-{uuid_str}.csv",index=False)
    print("_"*100)
    print("\nTest Preds:")   
    print(test_preds)

    if load_predictor:
        pass
    else:
        subprocess.check_call(['gsutil', '-m','cp','-r', models_name, f"{gcs_path_model}/AutogluonModels/{models_name}"], stderr=sys.stdout)
    
    if pubsub_topic is not None:

        logger.info("Training completed.\n\n\n")
        publisher.publish(topic_path, f"LOG_FILENAME:{LOG_FILENAME}:LOG_FILENAME\n\n\n".encode("utf-8"))
        logger.info(f"LOG_FILENAME:{LOG_FILENAME}:LOG_FILENAME\n\n\n")
        publisher.publish(topic_path, "Successfully Done!\n\n\n".encode("utf-8"))
    
    subprocess.check_call(['gsutil','cp', LOG_FILENAME, f"{gcs_path_logs}/{LOG_FILENAME}"], stderr=sys.stdout)
if __name__ == '__main__':
    fire.Fire(train_fn)