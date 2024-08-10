from sqlalchemy import Column, BigInteger, Integer, DateTime, String, Float, Enum, Text, TIMESTAMP,text ,Boolean
from database import db
import phpserialize

class MemberSubscription(db.Model):
    __tablename__ = 'wp_pms_member_subscriptions'

    id = Column(BigInteger, primary_key=True)
    user_id = Column(BigInteger, nullable=False)
    subscription_plan_id = Column(BigInteger, nullable=False)
    start_date = Column(DateTime)
    expiration_date = Column(DateTime)
    status = Column(String(32), nullable=False)
    payment_profile_id = Column(String(32), nullable=False)
    payment_gateway = Column(String(32), nullable=False)
    billing_amount = Column(Float, nullable=False)
    billing_duration = Column(Integer, nullable=False)
    billing_duration_unit = Column(String(32), nullable=False)
    billing_cycles = Column(Integer, nullable=False)
    billing_next_payment = Column(DateTime)
    billing_last_payment = Column(DateTime)
    trial_end = Column(DateTime)

    def __repr__(self):
        return f"<MemberSubscription(id={self.id}, user_id={self.user_id}, status={self.status})>"

class WpUser(db.Model):
    __tablename__ = 'wp_users'

    id = db.Column(db.BigInteger, primary_key=True, autoincrement=True)
    user_login = db.Column(db.String(60), nullable=False, index=True)
    user_pass = db.Column(db.String(255), nullable=False)
    user_nicename = db.Column(db.String(50), nullable=False, index=True)
    user_email = db.Column(db.String(100), nullable=False, index=True)
    user_url = db.Column(db.String(100))
    user_registered = db.Column(db.DateTime, nullable=False)
    user_activation_key = db.Column(db.String(255))
    user_status = db.Column(db.Integer, nullable=False, default=0)
    display_name = db.Column(db.String(250), nullable=False)

    def __repr__(self):
        return f"<User(id={self.id}, user_login={self.user_login}, user_email={self.user_email}, user_status={self.user_status})>"

class WpUserMeta(db.Model):
    __tablename__ = 'wp_usermeta'

    umeta_id = db.Column(db.BigInteger, primary_key=True, autoincrement=True, nullable=False)
    user_id = db.Column(db.BigInteger, nullable=False, index=True)
    meta_key = db.Column(db.String(255), nullable=True, index=True)
    meta_value = db.Column(db.Text, nullable=True)

    def __repr__(self):
        return f"<WpUserMeta(umeta_id={self.umeta_id}, user_id={self.user_id}, meta_key={self.meta_key})>"

class AmUsersJobs(db.Model):
    __tablename__ = 'am_users_jobs'

    user_id = db.Column(db.BigInteger, nullable=False)
    uuid = db.Column(db.Text(collation='utf8mb4_general_ci'), primary_key=True, index=True, nullable=False)
    job_name = db.Column(String(256), nullable=False, unique=True )
    model_name = db.Column(String(256), nullable=True, unique=True )
    start_time = db.Column(db.TIMESTAMP, nullable=False)
    end_time = db.Column(db.TIMESTAMP, nullable=True, default=None)
    status = db.Column(String(50), nullable=False)
    machine_type = db.Column(String(50), nullable=False)
    disk_type = db.Column(String(50), nullable=False)
    disk_size = db.Column(db.Float, nullable=False, default=0.)
    cost = db.Column(db.Float, nullable=True, default=None)
    label = Column(String(256), nullable=True, default=None)
    problem_type = Column(Enum('binary', 'multiclass', 'regression', name='problem_type'), nullable=False, default='multiclass')
    evaluation_metric = Column(String(256), nullable=True, default=None)
    prediction_type = Column(Enum('hard', 'soft'), nullable=False, default='soft')
    split_type = Column(Enum('train_test', 'KFold', 'StratifiedKFold'), nullable=False, default='train_test')
    preprocess = Column(Boolean(), nullable=False, default=False)
    feature_engineering = Column(Boolean(), nullable=False, default=False)

    def __repr__(self):
        return (
            f"<AmUsersJobs(user_id={self.user_id}, uuid={self.uuid}, job_name={self.job_name}, model_name={self.model_name}, "
            f"start_time={self.start_time}, end_time={self.end_time}, status={self.status}, machine_type={self.machine_type}, "
            f"disk_type={self.disk_type}, disk_size={self.disk_size}, cost={self.cost}, label={self.label}, "
            f"problem_type={self.problem_type}, evaluation_metric={self.evaluation_metric}, prediction_type={self.prediction_type}, "
            f"split_type={self.split_type}, preprocess={self.preprocess}, feature_engineering={self.feature_engineering})>"
        )

class AmUsersBalance(db.Model):
    __tablename__ = 'am_users_balance'

    transaction_id = db.Column(db.BigInteger, primary_key=True, autoincrement=True, nullable=False)
    user_id = db.Column(db.BigInteger, nullable=False)
    uuid = db.Column(db.Text(collation='utf8mb4_general_ci'), primary_key=True, index=True, nullable=False)
    balance = Column(Float, nullable=False, default=0)
    transaction_time = Column(TIMESTAMP, nullable=True, server_default=text('current_timestamp()'))
    transaction_type = Column(Enum('credit', 'debit', name='transaction_type'), nullable=False)
    transaction_amount = Column(Float, nullable=False)
    transaction_description = Column(String(256), nullable=True, default=None)


    def __repr__(self):
        return (
            f"<AmUsersBalance(transaction_id={self.transaction_id}, user_id={self.user_id}, uuid={self.uuid}, "
            f"balance={self.balance}, transaction_time={self.transaction_time}, transaction_type={self.transaction_type}, "
            f"transaction_amount={self.transaction_amount}, transaction_description={self.transaction_description})>"
        )



def get_application_password(user_email):
    application_password_meta = db.session.query(WpUser.id, WpUser.user_email, WpUserMeta.meta_key,
                                                 WpUserMeta.meta_value, WpUserMeta.umeta_id)\
        .outerjoin(WpUserMeta, WpUser.id == WpUserMeta.user_id)\
        .filter(WpUser.user_email == user_email, WpUserMeta.meta_key == "_application_passwords")\
        .order_by(WpUserMeta.umeta_id.desc())\
        .with_entities(WpUserMeta.meta_value).first()

    if not application_password_meta:
        return None

    serialized_data = application_password_meta[0]
    application_passwords = phpserialize.loads(serialized_data.encode('utf-8'))
    application_password_hash = application_passwords[0][b'password'].decode('utf-8')

    return application_password_hash

def get_user_ids():
    ids_370 = db.session.query(WpUser.id, WpUser.user_email, MemberSubscription.subscription_plan_id)\
    .outerjoin(MemberSubscription, WpUser.id == MemberSubscription.user_id)\
    .filter(MemberSubscription.subscription_plan_id == 370)\
    .with_entities(WpUser.user_email)\
    .all()
    ids_370 = [x[0] for x in ids_370]

    ids_371 = db.session.query(WpUser.id, WpUser.user_email, MemberSubscription.subscription_plan_id)\
    .outerjoin(MemberSubscription, WpUser.id == MemberSubscription.user_id)\
    .filter(MemberSubscription.subscription_plan_id == 371)\
    .with_entities(WpUser.user_email)\
    .all()
    ids_371 = [x[0] for x in ids_371]

    ids_372 = db.session.query(WpUser.id, WpUser.user_email, MemberSubscription.subscription_plan_id)\
    .outerjoin(MemberSubscription, WpUser.id == MemberSubscription.user_id)\
    .filter(MemberSubscription.subscription_plan_id == 372)\
    .with_entities(WpUser.user_email)\
    .all()
    ids_372 = [x[0] for x in ids_372]

    ids_814 = db.session.query(WpUser.id, WpUser.user_email, MemberSubscription.subscription_plan_id)\
    .outerjoin(MemberSubscription, WpUser.id == MemberSubscription.user_id)\
    .filter(MemberSubscription.subscription_plan_id == 814)\
    .with_entities(WpUser.user_email)\
    .all()
    ids_814 = [x[0] for x in ids_814]

    return ids_370, ids_371, ids_372, ids_814
